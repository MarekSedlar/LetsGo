<?php

namespace App\Presenters;

use Nette;
use App\Model;
use Nette\Application\UI;
use App\Forms;

class UsersPresenter extends BasePresenter
{
    /** @var Forms\UserFormFactory @inject */
    public $userFormFactory;

    /** @var string @persistent */
    public $ajax = 'on';

    public $user;

    /** @var string @persistent */
    public $filterRenderType = \Grido\Components\Filters\Filter::RENDER_INNER;

    public function startup(){
        parent::startup();
        $user = $this -> getUser();
        if(!$user -> isLoggedIn() OR !$this->getUser()->isAllowed('users', $this -> getParameter("action")))
        {
            $this -> redirect('Homepage:default');
        }
    }

    public function handleCloseTip(){
        $this->context->httpResponse->setCookie('grido-examples-first', 0, 0);
        $this->redirect('this');
    }

    protected function createComponentGrid($name){
        $grid = new \Grido\Grid($this, $name);
        
        //$this -> database = new Nette\Database\Connection();
        //var_dump($this->database);


        //die();
        $grid -> model = $this->database->table('users');
        
        $grid   -> addColumnText('username', 'Jméno')
                -> setSortable()
                -> setFilterText()
                -> setSuggestion();

        $grid   -> addColumnText('role', 'Oprávnění')
                -> setReplacement(
                    array(
                        'admin' => Nette\Utils\Html::el('b')->setText('Administrátoris'),
                        'user' => 'Uživatel')
                    )
                -> setSortable()
                -> setFilterText()
                -> setSuggestion();

        $grid   -> addColumnEmail('email', 'E-mail')
                -> setSortable()
                -> setFilterText();

        $grid   -> getColumn('email')
                -> cellPrototype
                -> class[] = 'center';

        if($this->getUser()->isAllowed('users', 'edit')){
            $grid   -> addActionHref('edit', 'Upravit')
                    -> setIcon('pencil');
        }

        if($this->getUser()->isAllowed('users', 'delete')){
            $grid   -> addActionHref('delete', 'Smazat')
                    -> setIcon('trash');
        }

        $operation = array('delete' => 'Smazat');
        $grid   -> setOperation($operation, $this -> handleOperations)
                -> setConfirm('delete', 'Vážně chcete smazat %i uživatelů?');
        $grid   -> filterRenderType = $this -> filterRenderType;
    }

    /**
     * Common handler for grid operations.
     * @param string $operation
     * @param array $id
     */
    public function handleOperations($operation, $id){
      $this -> actionDelete($id);
    }

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
            $form->addError('The username or password you entered is incorrect.');
            return;
        }
    }

protected function createComponentUserForm()
    {
        /*
        Formulář ignoruje ID v URL takže přesměrovává zpracování na /users/edit
        Při změně actionu ($form -> setAction) se formulář nezpracuje (nespustí se onSuccess)

        Nepodařilo se mi přijít na to, jak to opravit, snad mě poučíte Emotikona frown
        */
        if($this -> getParameter("action") == "edit" && $this -> getParameter("id"))
        {
            $id = $this -> getParameter("id");

            $userData = $this -> database -> table("users") -> get($id) -> toArray();
            $userData["id"] = $id;
            $this -> userFormFactory -> setData($userData);
        }

        return $this -> userFormFactory -> create(
            function() {
                $this -> redirect('Users:show');
            }
        );
    }

    /*public function renderEdit()
    {
        $this->template->anyVariable = 'any value';
    }*/

}
