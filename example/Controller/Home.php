<?php

class Controller_Home extends Controller_Base {

	//defaule website entrance
	public function indexAction(){

		echo '<p>Welcome to Kiss Framework</p>';

		//set page title
		$this->view->title = 'Example for Kiss Framework';

		//get square(4)
		$this->view->square4 = $this->view->getSquare(4);

		$this->render('home/welcome');
		echo 123;
	}

	public function hiAction(){
		$name = $_GET['username'];
		if(!$name) {
			$name = 'World';
		}

		echo 'Hello '.ucfirst($name);
	}

	/**called before each action**/
    public function before(){
        parent::before();
    }

	/**called after each action**/
    public function after(){
        parent::after();

		echo '<p>Current Controller: '. Kiss_Router::currController() . '</p>';
		echo '<p>Current Action: '. Kiss_Router::currAction() . '</p>';        
    }

}