<?php

namespace App\Presenters;

use Nette;
use App\Model;


class MojeStrankaPresenter extends BasePresenter
{

	public function renderDefault()
	{
		$this->template->anyVariable = 'any value';
	}

}
//www.seznam.cz/index.php?stranka=uzivatele&strankovani=5
//echo $_GET["stranka"];