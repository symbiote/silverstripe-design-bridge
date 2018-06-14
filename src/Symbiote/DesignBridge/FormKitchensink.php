<?php

namespace Symbiote\DesignBridge;

use Debug;
use Form;
use FieldList;
use FormAction;
use RequiredFields;
use ResetFormAction;
use TextField;
use EmailField;
use NumericField;
use DateField;
use PasswordField;
use CheckboxField;
use CheckboxSetField;
use DropdownField;
use OptionsetField;

class FormKitchensink extends Form
{
    public function __construct($controller, $name)
    {
        $fields = $this->getFields();
        $actions = $this->getActions();
        $this->setTemplate('Symbiote-DesignBridge-FormKitchensink');
        parent::__construct($controller, $name, $fields, $actions, $this->getFieldsValidator());
        //$this->setFormAction(Controller::join_links($controller->Link(), $name));
    }

    /**
     * @return FieldList
     */
    protected function getFields()
    {
        $fields = new FieldList();
        $fields->push(TextField::create('Name', 'Name')
            ->setRightTitle('eg. Sam'));
        $fields->push(DateField::create('BirthDate', 'Date of Birth')->setAttribute('type', 'date'));
        $fields->push(NumericField::create('Phone', 'Phone Number')->setAttribute('type', 'tel'));
        $fields->push(EmailField::create('Email', 'Your Email Address')->setAttribute('placeholder', 'name@domain.com'));
        $fields->push(PasswordField::create('Password', 'Password'));
        $fields->push(CheckboxField::create('StoreAsPlaintext', 'Store your password in the server as plaintext?'));
        $fields->push(CheckboxSetField::create('CheckboxOptions', 'Checkbox Options', array(
            'value1' => 'Option 1',
            'value2' => 'MENTORING JUVENILE OFFENDERS: I would like to hear more about the Juvenile Justice Veteran Mentoring Program. The program requires fortnightly contact with a young offender about to transition back into society.',
            'value3' => 'Option 3',
        )));
        $fields->push(DropdownField::create('Selectyman', 'Select the things', array(
            'selectme1' => 'Select',
            'selectme2' => 'No, select me!',
            'selectme3' => 'I\'m the best but my title is very very very long you know!'
            )));
        $fields->push(OptionsetField::create('RadioButtons', 'Select one thing', array(
            'selectone1' => 'Cat Person',
            'selectone2' => 'Dog Person',
            'selectone3' => 'Fish Person'
            )));
        $this->extend('updateFields', $fields);

        return $fields;
    }

    public function doSubmit(array $data)
    {
        exit('Successful submission!');
    }

    protected function getActions()
    {
        $actions = new FieldList();
        $actions->push(FormAction::create('doSubmit')->setTitle('Submit'));
        $actions->push(ResetFormAction::create('doReset')->addExtraClass('cancel')->setTitle('Reset Form'));
        $this->extend('updateActions', $actions);
        return $actions;
    }

    protected function getFieldsValidator()
    {
        $required = new RequiredFields(array(
            'Name', 'BirthDate', 'Email', 'Selectyman', 'StoreAsPlaintext', 'RadioButtons', 'CheckboxOptions'
        ));

        return $required;
    }
}
