<?php

class IndexController extends Zend_Controller_Action {

    /**
     * @property Posts_Model_DbTable_Posts posts table instance
     */
    private $_postsModel;

    /**
     * @property User_Model_DbTable_Users users table instance
     */
    private $_usersModel;

    /**
     * @property Model_Auth_Visitor visitor instance
     */
    private $_visitor;

    /**
     * @property Zend_Auth::getInstance()
     */
    private $_sessionAuthUser;

    /**
     * Called before each action
     * @param void
     * @return void
     */ 
    public function init()
    {
        $auth = Zend_Auth::getInstance();
        $this->_sessionAuthUser = $auth->getStorage()->read();

        if ($this->getRequest()->isXmlHttpRequest()) {
            $viewRenderFlag = ('json' == $this->_request->getParam('format') ? false : true);
            $this->_helper->ajax($viewRenderFlag)->setViewSuffix('phtml');
        }

        $this->_postsModel = new Posts_Model_DbTable_Posts();
        $this->_usersModel = new User_Model_DbTable_Users();
        $this->_visitor = $this->_helper->visitor();
        $this->view->visitor = $this->_visitor;
        
    }

    /**
     * Default preparation
     * @param array
     * @return void
     */
    private function _prepare()
    {
        // get neccessary counters
        $this->view->postsCount = $this->_postsModel->getCount();
        $this->view->usersCount = $this->_usersModel->getCount()->count;
        $this->view->usersOnlineCount = $this->_visitor->countUsersOnline();

        $defaultGeo = $this->_visitor->detectDefaultGeo(); //define default params for geo filtering to this visitor
        
        $this->view->defaultGeoId        = $defaultGeo['id'];
        $this->view->defaultGeoLeft      = $defaultGeo['left'];
        $this->view->defaultGeoRight     = $defaultGeo['right'];
        $this->view->defaultGeoLocation  = $defaultGeo['location'];

        $this->view->posts = Zend_Json::encode(array('category' => null)); // this is default responce 
    }

    /**
     * Check if some params (like sorting, category filtering etc) was saved and load then if they are
     * @param void
     * @return void
     */
    public function checkSavedParams()
    {   
        $filter = $this->_visitor->getSavedPostsFilter();

        $this->view->sort               = $filter['sort'];
        $this->view->geoId              = $filter['geoId'];
        $this->view->geoLeft            = $filter['geoLeft'];
        $this->view->geoRight           = $filter['geoRight'];
        $this->view->categories         = Zend_Json::encode($filter['categories']);
        $this->view->geoLocation        = $filter['geoLocation'];
    }

    /**
     * Action on index page
     * @param void
     * @return void
     */
    public function indexAction()
    {
        $this->_prepare();
        $this->checkSavedParams();

    }

    public function sqlAction()
    {
        $this->_prepare();
        $this->checkSavedParams();

    }


    /**
     * Action on urgent posts page
     * @param void
     * @return void
     */
    public function urgentAction()
    {
        $this->_prepare();
        $this->checkSavedParams();
    }

    /**
     * Action on top posts page
     * @param void
     * @return void
     */
    public function topAction()
    {
        $this->_prepare();
        $this->checkSavedParams();
    }

    /**
     * Action on page on one post category
     * @param void
     * @return void
     */
    public function categoryAction()
    {
        $this->_prepare();

        $this->view->categories = Zend_Json::encode(array($this->getRequest()->id));
        
        $this->_helper->viewRenderer('index');
    }


    public function setLanguageAction()
    {
 
        $request = $this->getRequest();
        $url     = $request->getParam('url');
        $locale  = new Bluembo_Localization($request);
        $locale->initLocale();

        if ( (int) $request->getParam('cabinet') == 1 ) {
            echo Zend_Json::encode(array('reboot'=>1));
            exit();
            //echo 1;// $this->_redirect($url, array('prependBase' => false));
        } else {
            $this->_redirect($url, array('prependBase' => false));
        }
    }

    public function userAlbumsAction()
    {
        // check params
        // todo: add visitor permission check for tab
        if (!$this->_hasParam('id')) throw new Model_Exception_PageNotFound();
        $userId = $this->_getParam('id');
        $users  = new User_Model_DbTable_Users();
        $user   = $users->getRowById($userId);
        if (!$user) throw new Model_Exception_PageNotFound();

        // get albums
        $this->view->user      = $user;
        $this->view->albums    = $user->getAlbums();
        $this->view->isVisitor = ($this->view->visitor()->hasIdentity() && $this->view->visitor()->id == $this->view->user->id);
    }

    public function ajaxSearchFriendsByPageAction()
    {
        if (!$this->_hasParam('id')) {
            return false;
        }
        $users = new User_Model_DbTable_Users;
        $user  = $users->getRowById($this->_getParam('id'));
        if (!is_object($user)) {
            return false;
        }

        $this->view->result = $user->getFriendsOnlineNextPage($this->_request->getParam('start'));
    }

    public function ajaxSearchFriendsAction()
    {
        $table = new User_Model_DbTable_Users;
        $user  = $table->getRowById($this->_getParam('id'));
        if (!is_object($user)) {
            return false;
        }

        $search    = $this->_request->getParam('search');
        $forselect = $this->_request->getParam('forselect');

        $friendsOnlineScroll = $this->_request->getParam('fos');

        $this->view->url = null;

        if ($forselect) {
            $notin = $this->_request->getParam('notin');

            $items = $user->getFriends(null, $search, null, null, 5, true, $notin);

            $result = $items->toArray();

            if (($to_user_id = $this->_request->getParam('to_user_id'))) {

                $users = new User_Model_DbTable_Users();
                $user  = $users->getRowById($to_user_id);

                $first_user = array();
                $first_user[] = array('id' => $user->id, 'text' => $user->firstName, 'selected' => 1);

                $result = array_merge($first_user, $result);
            }
            echo Zend_Json::encode($result);
            return;
        } else {


            if ($search) {
                $this->view->friends = $user->getFriends(null, $search);
            } else {
                $this->view->friends = $paginator           = $user->getFriends(1);
                $newPage             = ($paginator->getCurrentPageNumber() + 1 > $paginator->count()) ? null : $paginator->getCurrentPageNumber() + 1;

                if ($newPage) {
                    $this->view->url = $this->_helper->url->url(array('controller' => 'index', 'action' => 'load-friends', 'page' => $newPage, 'format' => 'json', 'id' => $user->id), null, true);
                }
            }
            $this->view->friends = $this->view->partial('main/user-friend-list-lines.phtml', array('user' => $user, 'friends' => $this->view->friends));
        }
    }

    public function loadFriendsAction()
    {
        $this->_helper->ajax();
        $table = new User_Model_DbTable_Users;
        $user  = $table->getRowById($this->_getParam('id'));
        if (!is_object($user)) {
            return false;
        }
        $this->view->friends = $user->getFriends($this->_request->getParam('page', 1));
        $paginator           = $this->view->friends;
        $this->view->friends = $this->view->partial('main/user-friend-list-lines.phtml', array('user' => $user, 'friends' => $this->view->friends));

        $newPage         = ($paginator->getCurrentPageNumber() + 1 > $paginator->count()) ? null : $paginator->getCurrentPageNumber() + 1;
        $this->view->url = null;
        if ($newPage) {
            $this->view->url = $this->_helper->url->url(array('page' => $newPage));
        }
    }

    public function ajaxLoadUsersAction()
    {

        // $this->_helper->ajax();

        $data = $this->_getParam('data');

        $visitor = $this->_helper->visitor();

        $this->view->result = $visitor->getUsersOnlineFiltered($data);
    }

    public function ajaxLoadPostAction()
    {

        $id = (int) $this->_getParam('id');
        $count_likes = (int) abs($this->_getParam('count_likes'));
        $blogTable = new User_Model_DbTable_Blogs();
        $topblogsSort['sort'] = array('ABS(ot.count_likes)', 'ot.id');
        if ($count_likes) {

            $topblogsSort['where'] = array('(ABS(ot.count_likes) = ' . $count_likes . ' AND ot.id < ' . $id . ' OR  ABS(ot.count_likes)  < ' . $count_likes . ')');
           

            $this->view->result = $blogTable->getBlogsForWidget($topblogsSort, 1, 100);
            // $topblogsSort['orwhere'] = array('ot.count_likes = ' . $count_likes . ' AND ot.id < ' . $id);       
        } else {
            $this->view->result = array();
        }
    }

    public function ajaxLoadUrgentpostAction()
    {

        $id    = (int) $this->_getParam('id');
        $items = (int) $this->_getParam('items');

        $blogTable = new User_Model_DbTable_Blogs();

        $urgentBlogWhere['where'] = array('ot.create_date > NOW() - INTERVAL 1 DAY');

        if ($id) {
            $urgentBlogWhere['where'][] = 'ot.id < ' . $id;
        }

        $items = ($items) ? $items : 10;

        $this->view->result = $blogTable->getBlogsForWidget($urgentBlogWhere, 1, $items);
    }

    /**
     * Find and return to view child nodes by parent ID
     * @param void
     * @return void
     */
    public function ajaxGeoChildAction()
    {
        $this->_helper->ajax();

        $parentId = $this->_getParam('parentId');
        $table    = new Model_DbTable_Geo();
        $rows     = $table->getGeoListByParent($parentId);

        $this->view->options = '';

        foreach ($rows as $row) {

            $this->view->options .= '<option' . (1 == $row->rightIdx - $row->leftIdx ? ' class="last"' : '') . ' value="' . $row->geoId . '">' . $row->name . '</option>';
        }
    }

    /**
     * Find and return to view parent nodes by child ID
     * @param void
     * @return void
     */
    public function ajaxGeoParentAction()
    {

        $this->_helper->ajax();

        $childId = $this->_getParam('childId');
        $table   = new Model_DbTable_Geo();
        $rows    = $table->getGeoListByChild($childId);

        $this->view->options = '';

        if (is_object($rows)) {

            foreach ($rows as $row) {

                $this->view->options .= '<option' . (1 == $row->rightIdx - $row->leftIdx ? ' class="last"' : '') . ' value="' . $row->geoId . '">' . $row->name . '</option>';
            }
        }
    }

    /**
     * Find and return a list containing the element and all overlying lists
     * @param int|string
     * @return json
     */
    public function ajaxGeoTreeForChildAction()
    {
        $this->view->result = 'wrong params';

        if (!$this->_hasParam('id')) {
            return;
        }

        $_parentId = (int) $this->_getParam('id');

        $_result = array();

        $nestedTable = new Model_DbTable_GeoNestedSet();
        $namesTable  = new Model_DbTable_Geo();

        // get data till root
        $_stopper = 10; // fuse from the loopback
        do {

            // get parent id for item
            $child = $nestedTable->getRowByGeoId($_parentId);

            if (!$child) {
                return;
            }

            $_parentId = (int) $child->parentId;

            // get list for chosen parent
            $names = $namesTable->getListByParent($_parentId);
            if (!$names) {
                return;
            }

            $_result[$_parentId] = $names->toArray();

            --$_stopper;
        } while ($_parentId && 0 < $_stopper);

        // return result
        $this->view->data = $_result;
    }

    /**
     * Find and return a sublist for the element
     * @param int|string
     * @return json
     */
    public function ajaxGeoListForParentAction()
    {
        if (!$this->_hasParam('id')) {
            $this->view->result = 'no params';
            return;
        }

        $_parentId = (int) $this->_getParam('id');

        $namesTable = new Model_DbTable_Geo();

        // get list for chosen parent
        $names = $namesTable->getListByParent($_parentId);

        if (!$names) {
            $this->view->result = 'wrong item ' . $_parentId;
            return;
        }

        // return result
        $this->view->data = array(
            $_parentId => $names->toArray()
        );

        $first = $names[0];

        if(!empty($this->_sessionAuthUser)){
            $this->_sessionAuthUser->geo_id      = (int) $first['geoId'];
            $this->_sessionAuthUser->geo_left    = (int) $first['leftIdx'];
            $this->_sessionAuthUser->geo_right   = (int) $first['rightIdx'];
        }

        $params= array( 
            'geoId'         => (int) $first['geoId'],
            'geoLeft'       => (int) $first['leftIdx'],
            'geoRight'      => (int) $first['rightIdx'],
            'geoLocation'   => $first['name']
        );

        $_SESSION['detectDefaultGeo'] = serialize(array($params));
    }

    /**
     * Find and return to view parent node by child ID
     * @param void
     * @return void
     */
    public function ajaxGeoParentOneAction()
    {

        $this->_helper->ajax();

        $childId = $this->_getParam('childId');
        $table   = new Model_DbTable_Geo();
        $row     = $table->getGeoOneByChild($childId);

        if (is_object($row)) {

            $this->view->result = array(
                'id' => $row->geoId,
                'parentId' => $row->parentId,
                'rootId' => $row->rootId,
                'level' => $row->level,
                'nameFull' => $row->nameFull
            );
        }
    }

    /**
     * Find and return to view full name for ID
     * @param void
     * @return void
     */
    public function ajaxGeoGetFullNameAction()
    {
        $this->_helper->ajax();

        $id    = $this->_getParam('id');
        $table = new Model_DbTable_Geo();
        $row   = $table->getById($id);

        $this->view->result = $row->nameFull;
    }

    public function ajaxGeoAutocompleteAction()
    {
        $find               = $this->_getParam('name_startsWith');
        if( !empty($find)  && trim($find)!="" ){
            $table              = new Model_DbTable_Geo();
            $this->view->result = $table->getGeoListAutocomplete($find)->toArray();
        } else {
            $this->view->result =  array(0=>array(
                                        'name'=>$this->view->translate('Whole world'),
                                        'id' =>'0',
                                        'left' =>'0',
                                        'right' =>'0'
                                    ));
         }
    }


    /**
     * Checking if geo server = geo client
     * @param void
     * @return void
     */


    public function ajaxGeoCheckAction()
    {
        $templang = null;
        $_session = new Zend_Session_Namespace('localization');

        $langArray = Bluembo_Localization::getLangArray();

        if(!empty($_GET['var']) && !empty($_session->language)){
            // Сравнить входит ли язык в массив русскоязычных 
            //var_dump($_GET['var']);
            // Создаем переменную $templang

            if (in_array($_GET['var'], $langArray)) {
                $templang = 'ru';
            } else {
                $templang = 'en';
            }
            // Сравниваем $templang с переменной которая лежит в сессии

            // Если всё плохо тогда назначаем
            if ($templang == $_session->language) {
                $this->view->result = 1;

            } else {
                $this->view->result = 0;
                $_session->language = $templang;
            }

            $_SESSION['country_ok'] = true;
            
        }
    }

  //   public function mapAction() {
  //       // $userModelDbTableSettings = new User_Model_DbTable_Settings();
  //       // $arrayMid = $userModelDbTableSettings->arrayMid( (int) $this->_sessionAuthUser->id );
  //       // var_dump($arrayMid);
  //       // exit();
  //   }

    /**
     * maps action
     * @param void
     * @return void
     */ 
    public function mapsAction()
    {

        if ( $_SERVER['REMOTE_ADDR'] == '127.0.0.1' ) {
            $_ip = '31.42.52.158';
        } else {
            $_ip = $_SERVER['REMOTE_ADDR'];
        }

        $_sxGeos   = new Bluembo_Geocheck_Geocheck();
        $_maps     = $_sxGeos->getCity($_ip);

        $userModelDbTableSettings = new User_Model_DbTable_Settings();
        $userModelDbTableUsers    = new User_Model_DbTable_Users();
        $_request = $this->getRequest()->getParams();
      
        $users = $userModelDbTableSettings->getAllUsersHeaders(0);
       
        if ( isset( $this->_sessionAuthUser->id ) ) { //if was registered
                   
            $userTable = & $userModelDbTableUsers; //= new User_Model_DbTable_Users();
            $userRow   = $userTable->getRowById( $this->_sessionAuthUser->id );
            $icon = "http://".$_SERVER['HTTP_HOST'] . $this->view->baseUrl(). '/'. $userRow->getHero()->getUrl(200);
            $latLot = $userModelDbTableSettings->latLonSelectUser( (int) $this->_sessionAuthUser->id ); // my hero 

            if ( ( $latLot[0]['lat'] == 0 ) || ( $latLot[0]['lon'] == 0 ) ) {
                $latLotOutputLat = isset( $_maps['lat'] ) ? $_maps['lat'] : 0;
                $latLotOutputLon = isset( $_maps['lon'] ) ? $_maps['lon'] : 0;
            }

            $this->view->icon = $icon;
            $this->view->lat  = $latLot[0]['lat'] ? $latLot[0]['lat'] : $latLotOutputLat;
            $this->view->lon  = $latLot[0]['lon'] ? $latLot[0]['lon'] : $latLotOutputLon;

            $latLonHome = $userModelDbTableSettings->latLonSelectUser( $this->_sessionAuthUser->id );

            # $selfHero = // $this->view->selfHero = Zend_Json::encode($selfHero);

           (int) $_uiCount = 0; // Removal of our hero 
            foreach ( $users as $key => $value ) {
                if( (int) $value['id'] == (int) $this->_sessionAuthUser->id ) {
                    $_uiDetete =  $_uiCount;
                }
                ++$_uiCount;
            }
            $_uiCount = null;
            unset( $users[$_uiDetete] );
            sort( $users );

            if ( isset( $this->_sessionAuthUser->id ) ) { 
                $this->_postsModel->setAuthorId( (int) $this->_sessionAuthUser->id );
                $this->_postsModel->setLimit(1);
                $_posts = $this->_postsModel->getCommon()->exportForPreview();
                $this->view->mySelfPost = Zend_Json::encode( $_posts );
            }   ///mapsArray.mySelfHero.mySelfPost

        } else { //if not was registered
            $this->view->icon =  "http://".$_SERVER['HTTP_HOST'] . $this->view->baseUrl(). '/'."images/heroes/200/w4.2.png";
        }

        (int) $_uiCount = 0;
        $usersIdPopup = array();
        foreach ( $users as $key => $value ) {

            $this->_postsModel->setAuthorId( (int) $value['id'] );
            $this->_postsModel->setLimit(1);
            $posts = $this->_postsModel->getCommon()->exportForPreview();
            $usersIdPopup[$value['id']] =  $posts;
           // array_push($usersIdPopup , array_shift( $_postsArray  ) );
            ++$_uiCount;
        }
        $_uiCount = null;

        $this->view->usersIdPopup = Zend_Json::encode($usersIdPopup);

        $this->view->users = Zend_Json::encode($users);
        $this->view->usersOnlineCount =  (int) $this->_visitor->countUsersOnline() ? $this->_visitor->countUsersOnline() : 0;

        if ( !isset( $this->_sessionAuthUser->id ) ) { 
            $this->view->latHome = isset( $_maps['lat'] ) ? $_maps['lat'] : 0; 
            $this->view->lonHome = isset( $_maps['lon'] ) ? $_maps['lon'] : 0; 
        }

    }

    /**
    * ajax maps action   save lon && lat 
    * @param void
    * @return void
    */ 

    public function ajaxMapsAction()
    {
        $this->_helper->ajax();
        $lon = (float) $this->_getParam('lon');
        $lat = (float) $this->_getParam('lat'); 
        if ( isset( $this->_sessionAuthUser->id ) ) {
            
            $userModelDbTableSettings = new User_Model_DbTable_Settings();
            $userModelDbTableSettings->boolLatLonUpdateUserSave( $lat , $lon , $this->_sessionAuthUser->id );

            $midLonLat = $userModelDbTableSettings->arrayMid( (int) $this->_sessionAuthUser->id );

            //print_r($midLonLat); exit();

            $this->view->result = array(
                'id'     => $this->_sessionAuthUser->id,
                'lon'    => $midLonLat['lon'],
                'lat'    => $midLonLat['lat'],
                'status' => "true" 
            );
        } else {
            $this->view->result = array();
        }
    }

   /**
    * ajax maps action Is No Post 
    * @param  void
    * @return void
    */
    public function ajaxMapsIsNoPostAction()
    {
        $this->_helper->ajax();
        $userId = (int) $this->_getParam('userId');

        if ( isset( $userId ) && ( $userId != 0 ) ) {

            $userModelDbTableSettings = new User_Model_DbTable_Settings();
            $info = $userModelDbTableSettings->otherInformation( $userId );

            $this->view->result = Zend_Json::encode(array(
                'id'   => $userId,
                'info' => $info 
            ));

        } else {
            $this->view->result = Zend_Json::encode(array(
                'Error' => "Wrong user id"
            ));
        }
    }


}