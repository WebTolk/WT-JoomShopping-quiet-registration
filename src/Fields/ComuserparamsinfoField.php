<?php
/**
 * @package    WT JoomShopping quiet registration
 * @version       1.0.0
 * @Author        Andrey Smirnikov, Sergey Tolkachyov, https://web-tolk.ru
 * @copyright     Copyright (C) 2024 Andrey Smirnikov
 * @license       GNU/GPL http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\Jshopping\Wt_jshopping_quiet_registration\Fields;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

class ComuserparamsinfoField extends NoteField
{

    protected $type = 'Comuserparamsinfo';

    /**
     * Method to get the field input markup for a spacer.
     * The spacer does not have accept input.
     *
     * @return  string  The field input markup.
     *
     * @since   1.7.0
     */
    protected function getInput(): string
    {
        return ' ';
    }

    /**
     * @return  string  The field label markup.
     *
     * @since   1.7.0
     */
    protected function getLabel(): string
    {
        $comUserParams = ComponentHelper::getParams('com_users');
		Factory::getApplication()->getLanguage()->load('com_users');

	   $allowUserRegistration = $comUserParams->get('allowUserRegistration', 0);

	   $allowUserRegistrationText = Text::_('COM_USERS_CONFIG_FIELD_ALLOWREGISTRATION_LABEL').' '.($allowUserRegistration ? '<span class="badge bg-success">'.Text::_('JYES').'</span>' : '<span class="badge bg-danger">'.Text::_('JNO').'</span>');
	    $useractivation = $comUserParams->get('useractivation',0);

	    $userActivationText = Text::_('COM_USERS_CONFIG_FIELD_USERACTIVATION_LABEL');

		switch ($useractivation)
		{
			case '1':
				$userActivationValue = '<span class="badge bg-info">'.Text::_('COM_USERS_CONFIG_FIELD_USERACTIVATION_OPTION_SELFACTIVATION').'</span>';
				break;
			case '2':
				$userActivationValue = '<span class="badge bg-success">'.Text::_('COM_USERS_CONFIG_FIELD_USERACTIVATION_OPTION_ADMINACTIVATION').'</span>';
				break;
			case '0':
			case 'default':
				$userActivationValue = '<span class="badge bg-danger">'.Text::_('JNO').'</span>';
				break;
		}
	    $userActivationText .= ' '.$userActivationValue;

        return '</div>
		<div class="card container shadow-sm w-100 p-0">
			<div class="card-body">
			<h4>'.Text::_('PLG_WT_JSHOPPING_QUIET_REGISTRATION_COM_USERS_PARAMS').'</h4>
				<ul class="list-group">
				<li class="list-group-item">'.$allowUserRegistrationText.'</li>
				<li class="list-group-item">'.$userActivationText.'</li>
				</ul>
			</div>
		</div><div>
		';
    }

    /**
     * Method to get the field title.
     *
     * @return  string  The field title.
     *
     * @since   1.7.0
     */
    protected function getTitle(): string
    {
        return $this->getLabel();
    }
}
