<?php

    class showinfo extends Plugin {
        var $name = 'Show Info';

        function showinfo($db, $site) {
            $this->Plugin($db, $site);
        }

        function getContent() {
            if (isset($_GET['typeId'])) {
                $item = $this->site->eveAccount->db->eveItem($_GET['typeId']);
                $this->name .= ': ' . $item->typename;
                $item->getGroup();
                $attr = $this->site->eveAccount->db->db->QueryA('SELECT a.valueInt, a.valueFloat, t.attributeName, t.displayName, u.displayName as unitName, g.icon, u.unitID '
                                                          . ' FROM invTypes i, dgmTypeAttributes a, dgmAttributeTypes t, eveUnits u, eveGraphics g '
                                                          . ' WHERE i.typeID = ? '
                                                          . ' AND i.typeId = a.typeId '
                                                          . ' AND a.attributeId = t.attributeId '
                                                          . ' AND u.unitId = t.unitId '
                                                          . ' AND g.graphicId = t.graphicId '
                                                          . ' AND t.published > 0', array($item->typeid));
                if (!is_array($attr))
                    $attr = array();

                if ($item->capacity > 0)
                    array_unshift($attr, array('displayname' => 'Capacity', 'valuefloat' => $item->capacity, 'icon' => '03_13'));
                if ($item->volume > 0)
                    array_unshift($attr, array('displayname' => 'Volume', 'valuefloat' => $item->volume, 'icon' => '02_09'));
                for ($i = 0; $i < count($attr); $i++) {
                    if (empty($attr[$i]['displayname']))
                        $attr[$i]['displayname'] = ucwords(ereg_replace("([A-Z]|[0-9]+)", " \\0", $attr[$i]['attributename'])); // "helloWorld" -> "Hello World"
                    else
                        $attr[$i]['displayname'] = ucwords($attr[$i]['displayname']);

                    if (isset($attr[$i]['unitname']) && ($attr[$i]['unitname'] == 'groupID')) {
                        $attr[$i]['valuestring'] = $this->site->eveAccount->db->eveItemGroup($attr[$i]['valueint'])->groupname;
                        if (!empty($this->site->eveAccount->db->eveItemGroup($attr[$i]['valueint'])->icon))
                            $attr[$i]['icon'] = $this->site->eveAccount->db->eveItemGroup($attr[$i]['valueint'])->icon;
                        $attr[$i]['unitname'] = '';
                    } else if (isset($attr[$i]['unitname']) && ($attr[$i]['unitname'] == 'typeID')) {
                        $attr[$i]['valuestring'] = $this->site->eveAccount->db->eveItem($attr[$i]['valueint'])->typename;
                        $attr[$i]['icon'] = $this->site->eveAccount->db->eveItem($attr[$i]['valueint'])->icon;
                        $attr[$i]['unitname'] = '';
                    } else if (isset($attr[$i]['unitid']) && ($attr[$i]['unitid'] == 117)) {
                        if ($attr[$i]['valueint'] == 1)
                            $attr[$i]['valuestring'] = 'Small';
                        else if ($attr[$i]['valueint'] == 2)
                            $attr[$i]['valuestring'] = 'Medium';
                        else if ($attr[$i]['valueint'] == 3)
                            $attr[$i]['valuestring'] = 'Large';
                        $attr[$i]['unitname'] = '';
                    } else if (isset($attr[$i]['unitname']) && (($attr[$i]['unitname'] == '%') && (!empty($attr[$i]['valuefloat'])))) {
                        if (($attr[$i]['unitid'] == 108) || ($attr[$i]['unitid'] == 111))
                            $attr[$i]['valuefloat'] = ((1 - $attr[$i]['valuefloat']) * 100);
                        else if ($attr[$i]['unitid'] == 109)
                            $attr[$i]['valuefloat'] = ($attr[$i]['valuefloat'] - 1) * 100;
                        else if ($attr[$i]['unitid'] == 127)
                            $attr[$i]['valuefloat'] = $attr[$i]['valuefloat'] * 100;
                    }
                }
            }

            $item->getDescription();
            $item->getPricing();
            $item = objectToArray($item, array('DBManager', 'eveDB'));

            return $this->render('info', array('attributes' => $attr, 'item' => $item));
        }
    }

?>