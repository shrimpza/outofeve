<?php

class DBManager {

    var $objs = array();
    var $numQueries = 0;

    function DBManager($conf) {
        $this->connect($conf);
    }

    function Connect($conf) {
        $this->conn = new PDO($conf['dsn'], $conf['user'], $conf['pass']);
    }

    function Query($sql, $params) {
        $this->numQueries++;
        $prep = $this->conn->prepare($sql);
        if ($prep->execute($params)) {
            return $prep;
        } else {
            print_r(array('errorInfo' => $prep->errorInfo(), 'sql' => $sql));
            return false;
        }
    }

    function rsToArray($rs) {
        $arr = $rs->fetchAll(PDO::FETCH_ASSOC);
        $res = array();
        for ($i = 0; $i < count($arr); $i++) {
            $res[] = array_change_key_case($arr[$i], CASE_LOWER);
        }
        return $res;
    }

    function QueryA($sql, $params) {
        $rs = $this->Query($sql, $params);
        if ($rs) {
            return $this->rsToArray($rs);
        } else {
            return false;
        }
    }

    function emptyRow($table) {
        $row = array();
        $r = $this->conn->Query('select * from ' . $table . ' limit 0');
        for ($i = 0; $i < $r->columnCount(); $i++) {
            $col = $r->getColumnMeta($i);
            $row[$col['name']] = '';
        }

        return $row;
    }

    function getObjectsArray($table, $where = '', $order = '') {
        if (!empty($where)) {
            $where = ' WHERE ' . $where;
        }
        if (!empty($order)) {
            $order = ' ORDER BY ' . $order;
        }
        $query = 'SELECT * FROM ' . $table . $where . $order;

        return $this->QueryA($query, array());
    }

    function getObject($table, $id = 0, $idCol = 'id') {
        for ($i = 0; $i < count($this->objs); $i++) {
            if (($this->objs[$i]->row[$idCol] == $id) && ($this->objs[$i]->table == $table)) {
                return $this->objs[$i];
            }
        }

        $this->objs[] = new DBObject($this, $table, $id, $idCol);

        return $this->objs[count($this->objs) - 1];
    }

    function getObjects($table, $where = '', $order = '') {
        $rowobjs = $this->getObjectsArray($table, $where, $order);

        if ($rowobjs) {
            $objs = array();
            foreach ($rowobjs as $row) {
                $newObj = new DBObject($this, $table, $row);
                $objs[] = $newObj;
                $this->objs[] = $newObj;
            }
            return $objs;
        } else {
            return false;
        }
    }

}

class DBObject {

    var $row = array();
    var $table = 'undefined';
    var $db;

    function DBObject($db, $table, $id, $idCol = 'id') {
        if (is_array($id)) {
            $this->row = $id;
        } else {
            if ($id == '0') {
                $this->row = $db->emptyRow($table);
            } else {
                $query = 'SELECT * FROM ' . $table . ' WHERE ' . $idCol . ' = ?';

                $tmp = $db->QueryA($query, array($id));

                if ($tmp) {
                    $this->row = $tmp[0];
                } else {
                    $this->row = $db->emptyRow($table);
                }
            }
        }

        $this->db = $db;
        $this->table = $table;
    }

    function save() {
        if ($this->row['id'] > 0) {
            $query = $this->updateRow();
        } else {
            $query = $this->insertRow();
        }

        $this->db->Query($query['q'], $query['values']);

        if ($this->row['id'] < 1) {
            $this->row['id'] = $this->db->conn->lastInsertId();
        }
    }

    function insertRow() {
        $fields = array();
        $values = array();
        $subst = array();
        foreach ($this->row as $field => $value) {
            $fields[] = $field;
            if (strtolower($field) == 'id') {
                $values[] = 0;
            } else {
                $values[] = $value;
            }
            $subst[] = '?';
        }

        return array('q' => 'INSERT INTO ' . $this->table . ' (' . implode(',', $fields) . ') VALUES (' . implode(',', $subst) . ')',
            'values' => $values);
    }

    function updateRow() {
        $fields = array();
        $values = array();
        foreach ($this->row as $field => $value) {
            if (strtolower($field) != 'id') {
                $fields[] = $field . " = ?";
                $values[] = $value;
            }
        }
        $values[] = $this->row['id'];
        return array('q' => 'UPDATE ' . $this->table . ' SET ' . implode(',', $fields) . ' WHERE id = ?',
            'values' => $values);
    }

    function delete() {
        $query = 'DELETE FROM ' . $this->table . ' WHERE id = ?';
        $this->db->Query($query, array($this->row['id']));
    }

    function __get($var) {
        if (in_array($var, array_keys($this->row))) {
            return stripslashes($this->row[$var]);
        }
    }

    function __set($var, $value) {
        if (in_array($var, array_keys($this->row))) {
            $this->row[$var] = $value;
        } else {
            $this->$var = $value;
        }
    }

    function __call($func, $params) {
        if (strpos($func, "get_") === 0) {
            $table = substr($func, 4);

            // one-to-one: get_table()
            if (isset($this->row[$table . '_id'])) {
                return $this->db->getObject($table, $this->row[$table . '_id']);
            }
            // many-to-many using link table: get_linked($table, $linkTable, $order)
            else if (substr($table, -6) == 'linked') {
                $table = $params[0];
                $linkTable = $params[1];

                if (!empty($params[2])) {
                    $order = ' ORDER BY ' . $order;
                }
                $query = 'SELECT ' . $table . '.* FROM ' . $linkTable . ' WHERE ' . $this->table . '_id = ' . $this->row['id'] . $order;
                $links = $this->db->QueryA($query, array());

                $linked = array();
                for ($i = 0; $i < count($links); $i++) {
                    $linked[] = $this->db->getObject($otherTable, $links[$i]['lnk']);
                }
            }
            // one-to-many without link: get_table_list($order)
            else if (substr($table, -5) == '_list') {
                if (!isset($params[0])) {
                    $params[0] = '';
                }
                if (isset($params[1]) && (trim($params[1]) != '')) {
                    $params[1] = ' and ' . $params[1];
                } else {
                    $params[1] = '';
                }
                return $this->db->getObjects(substr($table, 0, -5), $this->table . '_id = ' . $this->row['id'] . $params[1], $params[0]);
            }
        }

        throw new Exception('Call to unknown function ' . $func . ' on ' . get_class($this));
    }

}

?>