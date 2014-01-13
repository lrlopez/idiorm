<?php

class DMLTest extends PHPUnit_Extensions_Database_TestCase {

    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;

    // only instantiate PHPUnit_Extensions_Database_DB_IDatabaseConnection once per test
    private $conn = null;

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection() {
        if ($this->conn === null) {
            if (self::$pdo == null) {
                $obj = new PDO('sqlite::memory:');
                $obj->exec('CREATE TABLE user (id INTEGER PRIMARY KEY, username, description, age)');
                $obj->exec('CREATE TABLE profile (id INTEGER PRIMARY KEY, description, level)');
                $obj->exec('CREATE TABLE user_profile (user_id, profile_id, created_at, PRIMARY KEY (user_id, profile_id))');
                ORM::set_db($obj);                
                self::$pdo = $obj;
            }
            $this->conn = $this->createDefaultDBConnection(self::$pdo, ':memory:');
        }
        return $this->conn;
    }

    /**
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    public function getDataSet() {
        $dataSet = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();
        $dataSet->addTable('user', dirname(__FILE__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "user.csv");
        $dataSet->addTable('profile', dirname(__FILE__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "profile.csv");
        $dataSet->addTable('user_profile', dirname(__FILE__) . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "user_profile.csv");
        return $dataSet;
    }

    public function testFindOne() {
        $row = ORM::for_table('user')->find_one(1);
        $this->assertEquals('john', $row->username);
        $this->assertEquals('john', $row['username']);
        $this->assertEquals('john', $row->get('username'));
    }

    public function testFindMany() {
        $rows = ORM::for_table('user')->where_lt('age', 45)->find_many();
        $this->assertEquals(2, count($rows));
        $this->assertEquals('john', $rows[0]->username);
        $this->assertEquals('guest', $rows[1]->username);
    }

    public function testFindArray() {
        $rows = ORM::for_table('user')->select_many('id', 'age')->where_lt('age', 45)->find_array();
        $this->assertEquals(array(
            array('id' => '1', 'age' => '30'),
            array('id' => '3', 'age' => '40'),
        ), $rows);
    }

    public function testSelect() {
        $row = ORM::for_table('user')->select('username')->select('age')->find_one(1);
        $this->assertEquals('john', $row->username);
        $this->assertEquals(30, $row->age);
        $this->assertEquals(null, $row->id);   
    }

    public function testSelectMany() {
        $row = ORM::for_table('user')->select_many('username','age')->find_one(1);
        $this->assertEquals('john', $row->username);
        $this->assertEquals(30, $row->age);
        $this->assertEquals(null, $row->id);
        $row = ORM::for_table('user')->select_many(array('username','age'))->find_one(1);
        $this->assertEquals('john', $row->username);
        $this->assertEquals(30, $row->age);
        $this->assertEquals(null, $row->id);
    }

    public function testOrderAsc() {
        $rows = ORM::for_table('user')->order_by_asc('age')->find_many();
        $this->assertEquals(3, count($rows));
        $this->assertEquals('john', $rows[0]->username);
        $this->assertEquals('guest', $rows[1]->username);
        $this->assertEquals('test', $rows[2]->username);
    }

    public function testOrderDesc() {
        $rows = ORM::for_table('user')->order_by_desc('age')->find_many();
        $this->assertEquals(3, count($rows));
        $this->assertEquals('test', $rows[0]->username);
        $this->assertEquals('guest', $rows[1]->username);
        $this->assertEquals('john', $rows[2]->username);
    }

    public function testCount() {
        $num_rows = ORM::for_table('user')->where_lt('age', 45)->count();
        $this->assertEquals(2, $num_rows);
    }

    public function testSum() {
        $sum_rows = ORM::for_table('user')->where_lt('age', 45)->sum('age');
        $this->assertEquals(70, $sum_rows);
    }

    public function testAvg() {
        $sum_rows = ORM::for_table('user')->where_lt('age', 45)->avg('age');
        $this->assertEquals(35, $sum_rows);
    }

    public function testMax() {
        $sum_rows = ORM::for_table('user')->where_lt('age', 45)->max('age');
        $this->assertEquals(40, $sum_rows);
    }

    public function testMin() {
        $sum_rows = ORM::for_table('user')->where_lt('age', 45)->min('age');
        $this->assertEquals(30, $sum_rows);
    }

    public function testAddRow() {
        $row = ORM::for_table('user')->create()->set('username', 'idiorm')->set('description', 'it works!');
        $row->save();
        $this->assertEquals(4, $row->id);
    }

    public function testDeleteRow() {
        $row = ORM::for_table('user')->find_one(1);
        $row->delete();
        $this->assertEquals(false, ORM::for_table('user')->find_one(1));   
    }

    public function testDeleteMany() {
        $delete_ok = ORM::for_table('user')->where_lt('age', 45)->delete_many();
        $this->assertEquals(true, $delete_ok);
        $num_rows = ORM::for_table('user')->count();
        $this->assertEquals(1, $num_rows);
    }

    public function testDeleteResultSet() {
        $result_set = ORM::for_table('user')->where_lt('age', 45)->find_result_set();
        $result_set->delete();
        $num_rows = ORM::for_table('user')->count();
        $this->assertEquals(1, $num_rows);
    }

    public function testUpdateRow() {
        $row = ORM::for_table('user')->find_one(2);
        $row->username = "test123";
        $row->set('age', 90);
        $row->save();
        $row = ORM::for_table('user')->find_one(2);
        $this->assertEquals("test123", $row->username);
        $this->assertEquals("90", $row->age);
        $this->assertEquals("Testing User", $row->description);
    }

    public function testUpdateResultSet() {
        $result_set = ORM::for_table('user')->where_lt('age', 45)->find_result_set();
        $this->assertEquals(2, count($result_set));
        $result_set->set('age', 90);
        $result_set->save();
        $row_count = ORM::for_table('user')->where('age', 90)->count();
        $this->assertEquals(2, $row_count);
    }    
}