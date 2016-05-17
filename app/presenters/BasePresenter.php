<?php

namespace App\Presenters;

use Nette;
use App\Model;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    /** @var Nette\Database\Context */
    protected $database;

    public function __construct(Nette\Database\Context $database){
        $this -> database = $database;
    }

	protected function startup() {
        parent::startup();
        
        $acl = new Nette\Security\Permission;
        
        $acl->addRole('guest');
        $acl->addRole('user');
        $acl->addRole('admin');
       
        $acl->addResource('users');
        
        $acl->deny('guest');
        $acl->allow('user', 'users', 'show');
        $acl->allow('admin', Nette\Security\Permission::ALL, array('show', 'edit', 'delete', 'add'));
        
        $user = $this -> getUser();
        $user -> setAuthorizator($acl);
    }
}
