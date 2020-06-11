<?php
/**
 * Created by PhpStorm.
 * User: luis
 * Date: 12/03/20
 * Time: 13:20
 */

namespace IMN\Validators;


use Plenty\Validation\Validator;

class SettingsValidator extends Validator
{

    protected function defineAttributes()
    {

        $this->addString("name", true);
        $this->addString("value", false);
    }

//    public function buildCustomMessages()
//    {
//        // TODO: Implement buildCustomMessages() method.
//    }
}