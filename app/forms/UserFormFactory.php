<?php

namespace App\Forms;

use Nette;
use Nette\Application\UIyForm;
use App\Model;

class UserFormFactory
{
    //use Nette\SmartObject;

    const   PASSWORD_MIN_LENGTH = 3,
            USERNAME_MIN_LENGTH = 3,
            USERNAME_MAX_LENGTH = 25,
            USERNAME_PATTERN = '[a-zA-Z0-9]{3,25}';

    const   ERR_USERNAME            = 'Please enter username.',
            ERR_PASSWORD            = 'Please enter password.',
            ERR_EMAIL               = 'Please enter e-mail.',
            ERR_DUPLICITY_EMAIL     = 'This e-mail is already used.',
            ERR_DUPLICITY_USERNAME  = 'This username is already taken.';

    /** @var FormFactory */
    private $factory;

    /** @var Database\Context */
    private $database;

    private $data;

    public function __construct(FormFactory $factory, Nette\Database\Context $database)
    {
        $this -> factory = $factory;
        $this -> database = $database;
    }
        
    public function setData($data)
    {
        $this -> data = (array) $data;
    }

    private function getRoles()
    {
        return  [
                    'user'  => 'User',
                    'admin' => 'Administrator'
                ];
    }

    /**
     * @return Form
     */
    public function create($onSuccessCallback)
    {
        $form = $this -> factory -> create();

        /*
        Nette přidává hidden inputy až po tlačítku Send, takže se tyto dvě hodnoty neodesílají.
        Nepřišel jsem na způsob jak je dostat nad něj, i když jsou přidávány hned po vytvoření formuláře... Edit neprojde, protože $values -> id neexistuje a getParameter("id") již při zpracování neexistuje (viz UsersPresenter::createComponentUserForm komentář).
        */
        if(!empty($this -> data))
        {
            $form -> addHidden('edit', 'true');
            $form -> addHidden('id', $this -> data['id']);
        }

        $form   -> addText('username', 'Username:')
                -> setRequired(self::ERR_USERNAME)
                -> addRule($form::LENGTH, self::ERR_USERNAME, array(self::USERNAME_MIN_LENGTH, self::USERNAME_MAX_LENGTH)) // Kvůli JS
                -> addRule($form::PATTERN, self::ERR_USERNAME, self::USERNAME_PATTERN)
                -> setValue(
                    isset($this -> data['username']) ? htmlspecialchars($this -> data['username']) : NULL
                );

        $form   -> addPassword('password', 'Password:');

        $form   -> addText('email', 'E-mail:')
                -> setRequired(self::ERR_EMAIL)
                -> addRule($form::EMAIL, self::ERR_EMAIL)
                -> setValue(
                    isset($this -> data['email']) ? htmlspecialchars($this -> data['email']) : NULL
                );

        $form   -> addSelect('role', 'Group:', $this -> getRoles())
                -> setValue(
                    isset($this -> data['role']) ? $this -> data['role'] : NULL
                );

        $form   -> addSubmit('send', 'Send');

        $form   -> onSuccess[]  = array($this, 'userFormProcess');
        $form   -> onSuccess[]  = $onSuccessCallback;

        return $form;
    }

    public function userFormProcess($form, $values)
    {
        // input je přidaný až za submit tlačítko -> nikdy se nepošle
        $userID = isset($values -> id) ? $values -> id : 0;

        // Duplicita - username
        $duplicityUsername = $this -> database  -> table('users')
                                                -> where('username = ?', $values -> username)
                                                -> where('id != ?', $userID)
                                                -> count();
        if($duplicityUsername > 0)
        {
            $form -> addError(self::ERR_DUPLICITY_USERNAME);
            return;
        }

        // Duplicita - e-mail
        $duplicityEmail = $this -> database -> table('users')
                                            -> where('email = ?', $values -> email)
                                            -> where('id != ?', $userID)
                                            -> count();
        if($duplicityEmail > 0)
        {
            $form -> addError(self::ERR_DUPLICITY_EMAIL);
            return;
        }
        
        if(isset($values -> edit))
        {
            // Users:Edit
        }else {
            // Users:Add
            $formValues = $form -> getValues();
            $formValues["password"] = \Nette\Security\Passwords::hash($values -> password);
            
            $this -> database -> query("INSERT INTO users", $formValues);
        }
    }
}