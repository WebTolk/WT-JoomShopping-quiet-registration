<?php
/**
 * @package       WT JoomShopping quiet registration
 * @version       1.0.0
 * @Author        Andrey Smirnikov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Andrey Smirnikov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Jshopping\Wt_jshopping_quiet_registration\Extension;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class Wt_jshopping_quiet_registration extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadlanguage = true;

    protected $allowLegacyListeners = false;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterSaveCheckoutStep2' => 'onAfterSaveCheckoutStep2',
            'onLoadCheckoutStep5' => 'onLoadCheckoutStep5'
        ];
    }

    public function onAfterSaveCheckoutStep2(Event $event)
    {
        $adv_user = $event->getArgument(0);
        $user = $event->getArgument(1);
        $cart = $event->getArgument(2);

        $input = Factory::getApplication()->getInput();
        $username = $input->getString('f_name');
        $email = $input->getString('email');
        $state = $input->getString('state');
        $zip = $input->getString('zip');
        $city = $input->getString('city');
        $street = $input->getString('street');
        $phone = $input->getString('phone');

        // if user exists
        if (\JSFactory::getTable('userShop')->checkUserExistAjax($username, $email) !== '1')
        {
            $this->getApplication()->enqueueMessage('Пользователь (имя/емейл) ' . $username  . '/' . $email . ' существует');
        }
        else
        {
            $this->getApplication()->enqueueMessage('Пользователя (имя/емейл) ' . $username  . '/' . $email . ' нужно зарегистрировать');

            Factory::getLanguage()->load('com_users');
            $model = \JSFactory::getModel('userregister', 'Site');
            $userParams = $model->getUserParams();
            $useractivation = $userParams->get('useractivation');

            $this->getApplication()->enqueueMessage('user params: ' . print_r($userParams, true));
            if ($userParams->get('allowUserRegistration') == 0)
            {
                \JSError::raiseError(403, Text::_('Access Forbidden'));
                return;
            }

            $data = $model->getRegistrationDefaultData();
            $data->u_name = $username;
            $data->f_name = $username;
            $data->email = $email;
            $data->state = $state;
            $data->zip = $zip;
            $data->city = $city;
            $data->street = $street;
            $data->phone = $phone;

            $password = self::generatePassword();
            $data->password = $password;
            $data->password2 = $password;
            $data = (array)$data;

            $model->setData($data);

            if (!$model->check())
            {
                \JSError::raiseWarning('', $model->getError());
                return;
            }
            if (!$model->save())
            {
                \JSError::raiseWarning('', $model->getError());
                return;
            }
            $model->mailSend();

            // try activate user
            $token = $model->getUserJoomla()->getProperties()['activation'];
            $this->getApplication()->enqueueMessage('token is: ' . $token);
            $activateModel = \JSFactory::getModel('useractivate', 'Site');

            if (!$activateModel->check($token))
            {
                JSError::raiseError(403, $activateModel->getError());
                return;
            }

            $return = $activateModel->activate($token);
            $this->getApplication()->enqueueMessage('activation is ' . print_r((boolean)$return, true));
            //

            // try login user
            $loginModel = \JSFactory::getModel('userlogin', 'Site');
            $flag = $loginModel->login($data['username'], $password, array('remember' => false));
            $this->getApplication()->enqueueMessage('login with ' . $data['username'] . '/' . $password . ' was ' . ($flag ? 'success' : 'failed'));
            //

            $this->getApplication()->enqueueMessage('js factory user is: ' . print_r(get_object_vars(\JSFactory::getUser()), true));

            $message = $model->getMessageUserRegistration($useractivation);
            $this->getApplication()->enqueueMessage('message: ' . $message);
        }

        $this->getApplication()->enqueueMessage('onAfterSaveCheckoutStep2');
    }

    public function onLoadCheckoutStep5(Event $event)
    {
        $this->getApplication()->enqueueMessage('js user: ' . print_r(get_object_vars(\JSFactory::getUser()), true));
    }

    /**
     * Method to generate password.
     *
     * @return string Generated password.
     *
     * @since  1.0.0
     */
    public static function generatePassword(): string
    {
        $comUsersParams = ComponentHelper::getParams('com_users');
        // Минимальная длина. В Joomla по умолчанию минимальная длина - 8.
        $minimumLength = $comUsersParams->get('minimum_length', 8);
        // Минимальное количество цифр
        $minimumIntegers = $comUsersParams->get('minimum_integers', 4);
        // Минимальное количество символов
        $minimumSymbols = $comUsersParams->get('minimum_symbols', 0);
        // Минимальное количество букв в верхнем регистре
        $minimumUppercase = $comUsersParams->get('minimum_uppercase', 0);
        // Минимальное количество букв в нижнем регистре
        $minimumLowercase = $comUsersParams->get('minimum_lowercase', 0);

        // Словари
        $integers = ['1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $countIntegers = count($integers);

        $symbols = ['~', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-',
            '_', '.', ',', '<', '>', '?', '{', '}', '[',
            ']', '|', ':', ';'];
        $countSymbols = count($symbols);

        $lettersUppercase = ['A', 'B', 'C', 'D', 'E', 'F', 'G',
            'H', 'J', 'K', 'M', 'N', 'P', 'Q', 'R',
            'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $countlettersUppercase = count($lettersUppercase);

        $lettersLowercase = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'j', 'k',
            'm', 'n', 'p', 'q', 'r', 's', 't', 'u', 'v',
            'w', 'x', 'y', 'z'];
        $countLettersLowercase = count($lettersLowercase);

        // Массивы
        $passwordLowercase = [];
        $passwordIntegers = [];
        $passwordSymbols = [];
        $passwordUppercase = [];

        // Считаем длину пароля.
        $passwordLenght = $minimumIntegers + $minimumSymbols + $minimumUppercase + $minimumLowercase;
        $passwordLenght = ($passwordLenght < $minimumLength) ? $minimumLength : $passwordLenght;

        // Комбинации
        // основа пароля - нижний регистр и цифры. Остальное до кучи, поэтому если указаны символы и верхний регистр,
        // то вычитаем их количество из общей длины.  Везде, блин, могут оказаться нули...
        //
        // ПОВЕДЕНИЕ ПО УМОЛЧАНИЮ
        // По умолчанию все пустые значения. Буквы в нижнем регистре и цифры используем обязательно.
        if (empty($minimumIntegers) && empty($minimumSymbols) && empty($minimumUppercase) && empty($minimumLowercase))
        {
            // Пополам, если не указано.
            $minimumIntegers  = ceil($passwordLenght / 2); // Если общее количество - нечётное число
            $minimumLowercase = $passwordLenght - $minimumIntegers;
        }
        else
        {
            /**
             * Представим различные комбинации 4-х параметров, где часть - нули,
             * а часть - указаны. Мы должны зарезервировать "место" в пароле
             * под обязательные количества, а остальное поделить поровну между
             * нижним регистром и цифрами.
             * Соответственно - вычисляем "свободный остаток".
             *
             * Случаи противоречивых настроек???
             */
            $tmp_password_lenght = $passwordLenght;

            if (!empty($minimumUppercase))
            {
                $tmp_password_lenght = $tmp_password_lenght - $minimumUppercase;
            }

            if (!empty($minimumSymbols))
            {
                $tmp_password_lenght = $tmp_password_lenght - $minimumSymbols;
            }

            if (!empty($minimumIntegers))
            {
                $tmp_password_lenght = $tmp_password_lenght - $minimumIntegers;
            }

            if (!empty($minimumLowercase))
            {
                $tmp_password_lenght = $tmp_password_lenght - $minimumLowercase;
            }

            /**
             * Если "пустое место" есть - делим его пополам между числами
             * и нижним регистром.
             * Если есть уже указанные минимальные значения для
             * нижнего регистра и чисел, то прибавляем к ним.
             */

            if (!empty($tmp_password_lenght))
            {
                if (!empty($minimumIntegers) || !empty($minimumLowercase))
                {
                    $minimumIntegersTmp  = ceil($tmp_password_lenght / 2);
                    $minimumLowercaseTmp = $tmp_password_lenght - $minimumIntegersTmp;

                    $minimumIntegers = $minimumIntegers + $minimumIntegersTmp;
                    $minimumLowercase = $minimumLowercase + $minimumLowercaseTmp;
                }
                else
                {
                    $minimumIntegers  = ceil($tmp_password_lenght / 2);
                    $minimumLowercase = $tmp_password_lenght - $minimumIntegers;
                }
            }
        }


        // Собираем буквы в ВЕРХНЕМ регистре, если указаны в настройках
        if (!empty($minimumUppercase))
        {
            while (count($passwordUppercase) < $minimumUppercase)
            {
                $key                 = rand(0, ($countlettersUppercase - 1));
                $char                = $lettersUppercase[$key];
                $passwordUppercase[] = $char;
            }
        }

        // Собираем символы, если указаны в настройках
        if (!empty($minimumSymbols))
        {
            while (count($passwordSymbols) < $minimumSymbols)
            {
                $key               = rand(0, ($countSymbols - 1));
                $char              = $symbols[$key];
                $passwordSymbols[] = $char;
            }
        }

        // Собираем буквы в нижнем регистре
        if (!empty($minimumLowercase))
        {
            while (count($passwordLowercase) < $minimumLowercase)
            {
                $key                 = rand(0, ($countLettersLowercase - 1));
                $char                = $lettersLowercase[$key];
                $passwordLowercase[] = $char;
            }
        }

        // Собираем числа
        if (!empty($minimumIntegers))
        {
            while (count($passwordIntegers) < $minimumIntegers)
            {
                $key                = rand(0, ($countIntegers - 1));
                $char               = $integers[$key];
                $passwordIntegers[] = $char;
            }
        }

        $password = array_merge($passwordLowercase, $passwordIntegers, $passwordSymbols, $passwordUppercase);
        shuffle($password);
        $password = implode($password);

        return $password;
    }
}