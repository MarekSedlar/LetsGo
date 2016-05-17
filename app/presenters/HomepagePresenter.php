<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Application\UI;

class HomepagePresenter extends BasePresenter
{
    protected function createComponentSignUpForm()
    {
        $form = new UI\Form;

        $usernameErrorMsg = 'Please enter your username.';
        $form->addText('username', 'Username:')
            ->setRequired($usernameErrorMsg)// setRequired nastav poninné  
            ->addRule(UI\Form::LENGTH, $usernameErrorMsg, array(3, 25))// addRule přidej pravidlo, pravidlo na délku min-3znaky max-25znaků
            ->addRule(UI\Form::PATTERN, $usernameErrorMsg, '[a-zA-Z0-9]{3,25}');//addRule regulární výraz []

        $passwordErrorMsg = 'Please enter your password.';
        $form->addPassword('password', 'Password:')
            ->setRequired($passwordErrorMsg)
            ->addRule(UI\Form::MIN_LENGTH, $passwordErrorMsg, 3);

        $form->addCheckbox('remember', 'Keep me signed in');
        $form->addSubmit('send', 'Sign in');

        $form->onSuccess[] = array($this, 'signUpFormProcess');//onSuccess zaregistruje metodu a v případě že je vyplněný podle pravidel které jsem určil, tu metodu zavolá a předá ji té funkci signUpFormProcess
        return $form;
    }

    public function signUpFormProcess(UI\form $form, $values)
    {
        try
        {
            $this->user->setExpiration($values->remember ? '14 days' : '20 minutes');
            $this->user->login($values->username, $values->password);
            $this->redirect('Users:show');
        }catch(Nette\Security\AuthenticationException $e) 
        {
            $form->addError($e -> getMessage());
            return;
        }
    }

}
