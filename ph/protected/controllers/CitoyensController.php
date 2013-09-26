<?php
/**
 * CitoyensController.php
 *
 * @author: Tibor Katelbach <oceatoon@gmail.com>
 * Date: 7/23/12
 * Time: 12:25 AM
 */
class CitoyensController extends Controller {

	public function accessRules() {
		return array(
			// not logged in users should be able to login and view captcha images as well as errors
			array('allow', 'actions' => array('index','graph','register','register2')),
			// logged in users can do whatever they want to
			array('allow', 'users' => array('@')),
			// not logged in users can't do anything except above
			array('deny'),
		);
	}
    /**
     * Listing de tout les citoyen locaux filtrable et cherchable
     * par thématique
     */
	public function actionIndex() {
	    $this->render("index");
	}
	/**
	 * Point d'entrée pour gérer son compte 
	 */
    public function actionMoi() {
	    $this->render("compte");
	}
	/**
	 * upon Registration a email is send to the new user's email 
	 * he must click it to activate his account
	 * This is cleared by removing the tobeactivated field in the pixelactifs collection
	 */
    public function actionActivate($user) {
        $account = Yii::app()->mongodb->citoyens->findOne(array("_id"=>new MongoId($user)));
        if($account){
            Yii::app()->session["userId"] = $user;
            Yii::app()->session["userEmail"] = $account["email"];
            //remove tobeactivated attribute on account
            Yii::app()->mongodb->citoyens->update(array("_id"=>new MongoId($user)), array('$unset' => array("tobeactivated"=>"")));
        }
        //TODO : add notification to the cities,region,departement info panel
        
        //TODO : redirect to monPH page , inciter le rezotage local
        $this->redirect(Yii::app()->homeUrl);
                
	}
	/**
	 * Register
	 * on PH we registration requires only an email 
	 * test exists 
	 * otherwise add the DB 
	 * send validation mail 
	 */
	public function actionRegister()
	{
	    if(Yii::app()->request->isAjaxRequest && isset($_POST['registerEmail']) && !empty($_POST['registerEmail']))
		{
            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$_POST['registerEmail']));
            if($account){
                Yii::app()->session["userId"] = $account["_id"];
                Yii::app()->session["userEmail"] = $account["email"]; 
                echo json_encode(array("result"=>false, "id"=>"accountExist","msg"=>"Ce compte existe déjà."));
            }
            else {
                //validate isEmail
                $email = $_POST['registerEmail'];
                $name = "";
               if(preg_match('#^([\w.-])/<([\w.-]+@[\w.-]+\.[a-zA-Z]{2,6})/>$#',$email, $matches)) {
                  $name = $matches[0];
                  $email = $matches[1];
               }
               if(preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$email)) { 
                    $newAccount = array(
                    			'email'=>$email,
                                'tobeactivated' => true,
                                'adminNotified' => false,
                                'created' => time()
                                );
                    if(!empty($name))
                        $newAccount["name"] = $name;
                    Yii::app()->mongodb->citoyens->insert($newAccount);
                    Yii::app()->session["userId"] = $newAccount["_id"]; 
                    Yii::app()->session["userEmail"] = $newAccount["email"];
                    //send validation mail
                    //TODO : make emails as cron jobs
                    /*$message = new YiiMailMessage;
                    $message->view = 'validation';
                    $message->setSubject('Confirmer votre compte Pixel Humain');
                    $message->setBody(array("user"=>$newAccount["_id"]), 'text/html');
                    $message->addTo("oceatoon@gmail.com");//$_POST['registerEmail']
                    $message->from = Yii::app()->params['adminEmail'];
                    Yii::app()->mail->send($message);*/
                    
                    //TODO : add an admin notification
                    
                    echo json_encode(array("result"=>true, "id"=>$newAccount["_id"]));
               }else
                   echo json_encode(array("result"=>false, "msg"=>"Vous devez remplir un email valide."));
            }
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		exit;
	}
	/**
	 * Register to a secuure application, the unique pwd is linked to the application instance retreived by type
	 * test exists 
	 * otherwise add the DB 
	 * send validation mail 
	 */
	public function actionRegisterAppPwd()
	{
	    if(Yii::app()->request->isAjaxRequest && isset($_POST['registerEmail']) && !empty($_POST['registerEmail']) 
	                                          && isset($_POST['registerPwd']) && !empty($_POST['registerPwd']))
		{
		    //check application exists
		    if(isset($_POST['appKey']) && !empty($_POST['appKey']) 
	           && isset($_POST['appType']) && !empty($_POST['appType']))
	        {
	           $type = Yii::app()->mongodb->selectCollection($_POST['appType']);
	           $app = $type->findOne(array("_id"=>new MongoId($_POST['appKey'])));
               if($app)
               {
                    //validate isEmail
                    $email = $_POST['registerEmail'];
                    $name = "";
                   if(preg_match('#^([\w.-])/<([\w.-]+@[\w.-]+\.[a-zA-Z]{2,6})/>$#',$email, $matches)) 
                   {
                      $name = $matches[0];
                      $email = $matches[1];
                   }
                   if(preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$email)) 
                   { 
                        //test pwd
                        if( $app["pwd"] == $_POST['registerPwd'] ){
                            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$_POST['registerEmail']));
                            if($account){
                                Yii::app()->session["userId"] = $account["_id"];
                                Yii::app()->session["userEmail"] = $account["email"];
                                if ( !isset(Yii::app()->session["loggedIn"]) && !is_array(Yii::app()->session["loggedIn"]))
                                    Yii::app()->session["loggedIn"] =   array();
                                $tmp = Yii::app()->session["loggedIn"];
                                array_push( $tmp , $_POST['appKey'] );
                                Yii::app()->session["loggedIn"] = $tmp;
                                echo json_encode(array("result"=>true, "msg"=>"Vous êtes connecté à présent, Amusez vous bien."));
                            }
                            else
                                 echo json_encode(array("result"=>false, "msg"=>"Compte inconnue."));
                            
                        }else
                            echo json_encode(array("result"=>false, "msg"=>"Accés refusé."));
                    } else 
                       echo json_encode(array("result"=>false, "msg"=>"Email invalide"));
               } else 
                   echo json_encode(array("result"=>false, "msg"=>"Application invalide"));
		    }else{
                echo json_encode(array("result"=>false, "msg"=>"Vous Pourrez pas accéder a cette application"));
            }
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		
		exit;
	}
	/**
	 * More details added to the user s registration account
	 */
    public function actionRegister2()
	{
	    if(Yii::app()->request->isAjaxRequest && isset(Yii::app()->session["userId"]))
		{
            $account = Yii::app()->mongodb->citoyens->findOne(array("_id"=>new MongoId(Yii::app()->session["userId"])));
            if($account)
            {
                  $newInfos = array();
                  if( !empty($_POST['registerName']) )
                      $newInfos['name'] = $_POST['registerName'];
                  if( !empty($_POST['registerCP']) )
                      $newInfos['cp'] = $_POST['registerCP'];
                  if( isset($_POST['registerHelpout']) )
                  	  $newInfos['activeOnProject'] = $_POST['registerHelpout'];
                  if( !empty($_POST['helpJob']) )
                  	  $newInfos['positions'] = explode(",", $_POST['helpJob']);
                  /*if( isset($_POST['registerVieAssociative']) ){
                      //demande validation du responsable 
                  	  $newInfos['associations'] = explode(",", $_POST['listAssociation']);
                  }*/
                  if( !empty($_POST['tagsPA']) )
                      $newInfos['tags'] = explode(",", $_POST['tagsPA']);
                  $newInfos['type']=$_POST['typePA'];
                  $newInfos['country']=$_POST['countryPA'];
                  
                  //if a job in the list doesn't exist is new , add it to the jobType collection
                  $jobList = Yii::app()->mongodb->jobTypes->findOne(array("_id"=>new MongoId("5202375bc073efb084a9d2aa")));
                  foreach( explode(",", $_POST['helpJob']) as $job)
                  {
                      if(!in_array($job, $jobList['list']))
                      {
                          array_push($jobList['list'], $job);
                          Yii::app()->mongodb->jobTypes->update(array("_id"=>new MongoId("5202375bc073efb084a9d2aa")), array('$set' => array("list"=>$jobList['list'])));
                      }
                  }
                  
                  //if a job in the list doesn't exist is new , add it to the jobType collection
                  $tagsList = Yii::app()->mongodb->tags->findOne(array("_id"=>new MongoId("51b972ebe4b075a9690bbc5b")));
                  foreach( explode(",", $_POST['tagsPA']) as $tag)
                  {
                      if(!in_array($tag, $tagsList['list']))
                      {
                          array_push($tagsList['list'], $tag);
                          Yii::app()->mongodb->tags->update(array("_id"=>new MongoId("51b972ebe4b075a9690bbc5b")), array('$set' => array("list"=>$tagsList['list'])));
                      }
                  }
                  
                  //if a job in the list doesn't exist is new , add it to the group collection
                 /* $newAsso = false;
                  foreach( explode(",", $_POST['listAssociation']) as $asso)
                  {
                      if(!Yii::app()->mongodb->group->findOne(array("name"=>$asso)))
                          Yii::app()->mongodb->group->insert(array("name"=>$asso,
                          										   "type"=>"association",
                                                                   'tobeValidated' => true,
                                							 	   'adminNotified' => false));
                      $newAsso = $asso;
                  }*/
                  
                  $where = array("_id" => new MongoId(Yii::app()->session["userId"]));	
                  Yii::app()->mongodb->citoyens->update($where, array('$set' => $newInfos));
                  $result = array("result"=>true,"msg"=>"Vos Données ont bien été enregistrées.");
                  
                  echo json_encode($result); 
            } else 
                  echo json_encode(array("result"=>false, "id"=>"accountNotExist ".Yii::app()->session["userId"],"msg"=>"Ce compte n'existe plus."));
                
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		exit;
	}
	
    public function actionInvitation()
	{
	    if(Yii::app()->request->isAjaxRequest && isset($_POST['inviteEmail']) && !empty($_POST['inviteEmail']))
		{
            $account = Yii::app()->mongodb->citoyens->findOne(array("email"=>$_POST['inviteEmail']));
            $sponsor = Yii::app()->mongodb->citoyens->findOne(array("_id"=>new MongoId(Yii::app()->session["userId"])));
            if($account){
                //the sponsored user allready exists 
                //simply add it to the sponsors conenctions 
                $where = array("_id" => new MongoId(Yii::app()->session["userId"]));	
                $connect = (isset($sponsor["connect"])) ? array_push($connect["connect"], $account["_id"]) : array($account["_id"]);
                Yii::app()->mongodb->citoyens->update($where, array('$set' => array("connect"=>$connect )));
                echo json_encode(array("result"=>false, "id"=>"accountExist","msg"=>"Merci pour cette action de partage. "));
            }
            else 
            {
                //validate isEmail
               if(preg_match('#^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$#',$_POST['inviteEmail'])) { 
                    $newAccount = array(
                    			'email'=>$_POST['inviteEmail'],
                                'invitedBy'=>Yii::app()->session["userId"],
                                'tobeactivated' => true,
                                'adminNotified' => false,
                                'created' => time(),
                                'type'=>$_POST['typeInvite']
                                );
                    if( isset($_POST['inviteName']) )
                      $newAccount['name'] = $_POST['inviteName'];
                      
                    Yii::app()->mongodb->citoyens->insert($newAccount);
                    //send validation mail
                    //TODO : make emails as cron jobs
                    $message = new YiiMailMessage;
                    $message->view = 'invitation';
                    $name = (isset($sponsor["name"])) ? "par ".$sponsor["name"] : "par ".$sponsor["email"];
                    $message->setSubject('Invitation au projet Pixel Humain '.$name);
                    $message->setBody(array("user"=>$newAccount["_id"],
                                            "sponsorName"=>$name), 'text/html');
                    $message->addTo("oceatoon@gmail.com");//$_POST['inviteEmail']
                    $message->from = Yii::app()->params['adminEmail'];
                    Yii::app()->mail->send($message);
                    
                    //TODO : add an admin notification
                    
                    //simply add it to the sponsors conenctions 
                    $where = array("_id" => new MongoId(Yii::app()->session["userId"]));	
                    $connect = (isset($sponsor["connect"])) ? array_push($connect["connect"], $account["_id"]) : array($account["_id"]);
                    Yii::app()->mongodb->citoyens->update($where, array('$set' => array("connect"=>$connect )));
                    
                    echo json_encode(array("result"=>true, "id"=>$newAccount["_id"],"msg"=>"Meci pour votre contribution.".
                    																		"<br/> Plus on est nombreux, mieux ca marchera.".
                    																		"<br/> Plus on est de fous, plus on rit.".
                                                                                            "<br/>« Plus on est nombreux plus on crie dans le désert. »"));
               }else
                   echo json_encode(array("result"=>false, "msg"=>"Vous devez remplir un email valide."));
            }
		} else
		    echo json_encode(array("result"=>false, "msg"=>"Cette requete ne peut aboutir."));
		exit;
	}
}