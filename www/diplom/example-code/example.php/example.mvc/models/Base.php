<?php

/**
 * Description of Base
 *
 * @author user3
 */
class Model_DbTable_Base extends Bluembo_Db_Table implements Bluembo_ObserverableInterface{

    protected $_rowClass    = 'Model_DbRow_Base';
    protected $_rowsetClass = 'Model_DbRowset_Base';

     /**
     * @property array
     */
    private $_observers = array();

    /**
     * @property array
     */
    protected $_observerParams = array();

    /**
     * Initilizing
     * @param void
     * @return void
     */
    public function init()
    {
        $this->attach(new Synchronizer_Model_EventsListener());
        $this->attach(new News_Model_SourceEventsListener());
        $this->attach(new News_Model_EventsListener());
    }

    /**
     * Сделать заготовку селекта с учетом приватности.
     * @param int|string $objectId - опциональный идентификатор, если выбрать нужно только один элемент
     * @return Zend_Table_Select
     */
    public function privateSelect($objectId = null, $select = null, $newAllowed = false)
    {
        $_thisTableName       = $this->info('name');
        $_tabsTableName       = 'data_tabs_privacy';
        $_tabsCustomTableName = 'data_tabs_privacy_custom';
        $_friendsTableName    = 'data_friends';
        $_privacyTableName    = 'data_privacy_custom';

        // make base select
        if($select){
            $select = $this->select()->setIntegrityCheck(false)
                ->from(array('ot' => $_thisTableName), $select);
        }else{
            $select = $this->select()->setIntegrityCheck(false)
                ->from(array('ot' => $_thisTableName), 'ot.*');
        }

        if ($objectId) {
            $select
                    ->where('ot.id=?', $objectId)
            ;
        }

        // get friends
        $_visitorId = $this->_getVisitorId();
        if ($_visitorId) {
            $select
                    ->joinLeft(array('ft' => $_friendsTableName), '(ft.user_id_init=' . $_visitorId . ' AND ft.user_id_friend=ot.user_id) OR (ft.user_id_friend=' . $_visitorId . ' AND ft.user_id_init=ot.user_id)', array())
            ;
        }

        $_tabId = false;
        $_tabType = false;

        if('data_blog' == $_thisTableName){

            $_tabId = 1;
            $_tabType = 'Blog';

            if($newAllowed){
                $select->where('ot.is_new=1 OR ot.user_id=?', $_visitorId);
            }else{
                $select->where('ot.is_new=0');
            }

        }elseif('data_album' == $_thisTableName){

            $_tabId = 3;
            $_tabType = 'Album';

        }

        // check tab access for visitor
        if ($_tabId) {
            if ($_visitorId) {
                $select
                        ->joinLeft(array('tp' => $_tabsTableName), 'ot.user_id=tp.user_id AND tp.tab_id=' . $_tabId, array())
                        ->joinLeft(array('tc' => $_tabsCustomTableName), 'tp.id=tc.user_tab AND tc.to_user=' . $_visitorId, array())
                        ->where('ot.user_id=? OR tp.privacy=0 OR tp.privacy IS NULL OR (tp.privacy=1 AND NOT ft.approved IS NULL) OR (tp.privacy=3 AND NOT tc.to_user IS NULL)', $_visitorId)
                ;
            } else {
                $select
                        ->joinLeft(array('tp' => $_tabsTableName), 'ot.user_id=tp.user_id AND tp.tab_id=' . $_tabId, array())
                        ->where('tp.privacy=0 OR tp.privacy IS NULL')
                ;
            }
        }

        // check access to items for visitor
        if ($_visitorId) {
            if ($_tabType) {
                $select
                        ->joinLeft(array('pt' => $_privacyTableName), 'pt.object_type="' . $_tabType . '" AND pt.object_id=ot.id AND pt.user_id=' . $_visitorId, array())
                        ->where('ot.user_id=? OR ot.privacy=0 OR (ot.privacy=1 AND NOT ft.approved IS NULL) OR (ot.privacy=3 AND NOT pt.user_id IS NULL)', $_visitorId)
                ;
            } else {
                $select
                        ->where('ot.user_id=? OR ot.privacy=0 OR (ot.privacy=1 AND NOT ft.approved IS NULL)', $_visitorId)
                ;
            }
        } else {
            $select
                    ->where('ot.privacy = 0')
            ;
        }

        return $select;
    }

    /**
     * Для указанных объектов дать список имеющих доступ пользователей
     * Эта функция устареет при изменении механизма приватности.
     * Сейчас для уменьшения нагрузки она работает несколькими последовательными запросами:
     * 1. выбрать все объекты, прицепив для блогов и альбомов приватность таба автора
     * 2. перебрать объекты, выбирая идентификаторы кастомных настроек
     * 3. сделать запрос для выборок друзей
     * 4. сделать запрос для выборок кастомных наборов юзеров
     * 5. сделать запрос для выборок кастомных наборов юзеров по табам
     * 6. собрать результаты на отдачу
     * @param array $objectIds - список идентификаторов объектов
     * @return array - двумерный массив, первый ключ user_id, второй ключ object_id
     */
    public function privateCheck($objectIds = null)
    {
        // 1. выбрать все объекты, прицепив для блогов и альбомов приватность таба автора
        $_thisTableName = $this->info('name');

        $objectsSelect = $this->select()
                ->from(array('ot' => $_thisTableName), 'ot.*')
                ->where('id IN(?)', implode($objectIds))
        ;

        $_tabId = ( 'data_blog' == $_thisTableName ? 1 : ( 'data_album' == $_thisTableName ? 3 : false ));
        if ($_tabId) {
            $objectsSelect->setIntegrityCheck(false)
                    ->joinLeft(array('tp' => 'data_tabs_privacy'), 'ot.user_id=tp.user_id AND tp.tab_id=' . $_tabId, array('tab_privacy' => 'tp.privacy'))
            ;
        }

        $objects = $this->fetchAll($objectsSelect);

        // 2. перебрать объекты, выбирая идентификаторы кастомных настроек
        $_noAccess = array();
        $_fullAccess = array();
        $_friendsAccess = array();
        $_customAccess = array();

        foreach ($objects as $object) {
            $_privacy = (int) $object->privaсy;
            $_tabPrivacy = (empty($object->tab_privaсy) ? 2 : (int) $object->tab_privaсy);
            // 0 - все, 2 - только я, 1 - друзья, 2 - кастом
            if($_tabPrivacy==2) $_privacy = 2;
            if($_tabPrivacy==2) $_privacy = 2;
        }

        $_tabsTableName    = 'data_tabs_privacy';
        $_friendsTableName = 'data_friends';
        $_privacyTableName = 'data_privacy_custom';

        // make base select
        $select = $this->select()->setIntegrityCheck(false)
                ->from(array('ot' => $_thisTableName), 'ot.*');
        if ($objectId) {
            $select
                    ->where('ot.id=?', $objectId)
            ;
        }

        // get friends
        $_visitorId = $this->_getVisitorId();
        if ($_visitorId) {
            $select
                    ->joinLeft(array('ft' => $_friendsTableName), '(ft.user_id_init=' . $_visitorId . ' AND ft.user_id_friend=ot.user_id) OR (ft.user_id_friend=' . $_visitorId . ' AND ft.user_id_init=ot.user_id)', array())
            ;
        }

        // check tab access for visitor
        $_tabId = ( 'data_blog' == $_thisTableName ? 1 : ( 'data_album' == $_thisTableName ? 3 : false ));
        if ($_tabId) {
            if ($_visitorId) {
                $select
                        ->joinLeft(array('tp' => $_tabsTableName), 'ot.user_id=tp.user_id AND tp.tab_id=' . $_tabId, array())
                        ->joinLeft(array('tc' => $_tabsCustomTableName), 'tp.id=tc.user_tab AND tc.to_user=' . $_visitorId, array())
                        ->where('ot.user_id=? OR tp.privacy=0 OR (tp.privacy=1 AND NOT ft.approved IS NULL) OR (tp.privacy=3 AND NOT tc.to_user IS NULL)', $_visitorId)
                ;
            } else {
                $select
                        ->joinLeft(array('tp' => $_tabsTableName), 'ot.user_id=tp.user_id AND tp.tab_id=' . $_tabId, array())
                        ->where('tp.privacy=0')
                ;
            }
        }

        // check access to items for visitor
        if ($_visitorId) {
            $_tabType = ( 'data_blog' == $_thisTableName ? 'Blog' : ( 'data_album' == $_thisTableName ? 'Album' : false ));
            if ($_tabType) {
                $select
                        ->joinLeft(array('pt' => $_privacyTableName), 'pt.object_type="' . $_tabType . '" AND pt.object_id=ot.id AND pt.user_id=' . $_visitorId, array())
                        ->where('ot.user_id=? OR ot.privacy=0 OR (ot.privacy=1 AND NOT ft.approved IS NULL) OR (ot.privacy=3 AND NOT pt.user_id IS NULL)', $_visitorId)
                ;
            } else {
                $select
                        ->where('ot.user_id=? OR ot.privacy=0 OR (ot.privacy=1 AND NOT ft.approved IS NULL)', $_visitorId)
                ;
            }
        } else {
            $select
                    ->where('ot.privacy = 0')
            ;
        }

        return $select;
    }

//    public function setPrivacy($data){
//        $row = $this->getRowById($data['object_id']);
//        $row->privacy = $data['privacy'];
//        return $row->save();
//    }

    /**
     * @return Model_Auth_Visitor
     */
    protected function _getVisitor()
    {
        return Zend_Registry::get('visitor');
    }

    /**
     * @return int or false
     */
    protected function _getVisitorId()
    {
        $_visitor = $this->_getVisitor();
        if ($_visitor->hasIdentity()) {
            return $_visitor->id;
        } else {
            return false;
        }
    }

    public static function getClassFileName()
    {
        $className = get_called_class();
        $pieces    = explode('_', $className);
        return end($pieces);
    }

    /**
     * Return all fileds, been modified in this row before last update/insert
     * @param void
     * @return array
     */ 
    public function getObserverParams()
    {   
        return $this->_observerParams;
    }

    /**
     * Attach new observer to listen this class
     * @param Bluembo_ObserverInterface
     * @return void
     */ 
    public function attach(Bluembo_ObserverInterface $observer) 
    {
        $this->_observers[] = $observer;
    }
    
    /**
     * Detach observer from listening this class
     * @param Bluembo_ObserverInterface
     * @return void
     */ 
    public function detach(Bluembo_ObserverInterface $observer)
    {
        $key = array_search($observer,$this->_observers, true);
        if($key){
            unset($this->_observers[$key]);
        }
    }

    /**
     * Inform all observers that this class been chaged
     * @param void
     * @return void
     */
    public function notify() 
    {
        foreach ($this->_observers as $value) {
            $value->update($this);
        }
    }

    /**
     * Overriding insert method for sending notify to observers
     * @param  array  $data  Column-value pairs.
     * @return mixed         The primary key of the row inserted.
     */
    public function insert(array $data)
    {
        $result = parent::insert($data);

        $data['id'] = $result;
        $data['sqlType'] = 'insert';

        $this->_observerParams = $data;
        $this->notify();

        return $result;
    }

    /**
     * Overriding update method for sending notify to observers
     * @param  array        $data  Column-value pairs.
     * @param  array|string $where An SQL WHERE clause, or an array of SQL WHERE clauses.
     * @return int          The number of rows updated.
     */
    public function update(array $data, $where)
    {
        if(is_array($where)){
            $where = implode(',', $where);
        }

        $query = $this->select()->from($this->_name, 'id')->where($where);
        $ids = $this->fetchAll($query)->toArray();
        
        $result = parent::update($data, $where);

        $data['sqlType'] = 'update';
        $data['ids'] = $ids;
        
        $this->_observerParams = $data;
        $this->notify();

        return $result;
    }

    /**
     * Overriding delete method for sending notify to observers
     * @param  array|string $where SQL WHERE clause(s).
     * @return int          The number of rows deleted.
     */
    public function delete($where)
    {
        $result = parent::delete($where);

        $data = array();
        $data['sqlType'] = 'delete';
        $data['where'] = $where;

        $this->_observerParams = $data;
        // $this->notify();

        return $result;
    }


}