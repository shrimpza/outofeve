<?php

class showinfo extends Plugin {

    var $name = 'Show Info';

    function showinfo($db, $site) {
        $this->Plugin($db, $site);
    }

    function getContent() {
        if (isset($_GET['typeId'])) {
            $item = eveDB::getInstance()->eveItem($_GET['typeId']);
            $this->name .= ': ' . $item->typename;
            $item->getGroup();
            $attr = eveDB::getInstance()->db->QueryA('select a.valueInt, a.valueFloat, at.attributeName, at.displayName,
                                                        u.displayName as unitName, i.iconFile as icon, u.unitID
                                                      from invTypes t
                                                        inner join dgmTypeAttributes a on a.typeId = t.typeId
                                                        inner join dgmAttributeTypes at on at.attributeId = a.attributeId
                                                        inner join eveUnits u on u.unitId = at.unitId
                                                        left join eveIcons i on i.iconId = at.iconId
                                                      where t.typeID = ?
                                                        and at.published > 0', array($item->typeid));
            if (!is_array($attr)) {
                $attr = array();
            }

            if ($item->capacity > 0) {
                array_unshift($attr, array(
                    'displayname' => 'Capacity',
                    'valuefloat' => $item->capacity,
                    'icon' => itemGraphic::getItemGraphic(0, '03_13')
                ));
            }

            if ($item->volume > 0) {
                array_unshift($attr, array(
                    'displayname' => 'Volume',
                    'valuefloat' => $item->volume,
                    'icon' => itemGraphic::getItemGraphic(0, '02_09')
                ));
            }

            for ($i = 0; $i < count($attr); $i++) {
                if (empty($attr[$i]['displayname'])) {
                    $attr[$i]['displayname'] = ucwords(ereg_replace("([A-Z]|[0-9]+)", " \\0", $attr[$i]['attributename']));
                    // "helloWorld" -> "Hello World"
                } else {
                    $attr[$i]['displayname'] = ucwords($attr[$i]['displayname']);
                }

                if (!empty($attr[$i]['icon']) && is_string($attr[$i]['icon'])) {
                    $attr[$i]['icon'] = itemGraphic::getItemGraphic(0, $attr[$i]['icon']);
                }

                if (isset($attr[$i]['unitname']) && ($attr[$i]['unitname'] == 'groupID')) {
                    $grp = eveDB::getInstance()->eveItemGroup($attr[$i]['valueint']);
                    $attr[$i]['valuestring'] = $grp->groupname;
                    $attr[$i]['icon'] = $grp->icon;
                    $attr[$i]['unitname'] = '';
                } else if (isset($attr[$i]['unitname']) && ($attr[$i]['unitname'] == 'typeID')) {
                    $type = eveDB::getInstance()->eveItem(isset($attr[$i]['valueint']) ? $attr[$i]['valueint'] : round($attr[$i]['valuefloat']));
                    $attr[$i]['valuestring'] = $type->typename;
                    $attr[$i]['icon'] = $type->icon;
                    $attr[$i]['unitname'] = '';
                } else if (isset($attr[$i]['unitname']) && ($attr[$i]['unitname'] == 'attributeID')) {
                    $attr[$i]['valueint'] = empty($attr[$i]['valueint']) ? $attr[$i]['valuefloat'] : $attr[$i]['valueint'];
                    $attrName = eveDB::getInstance()->db->QueryA('select displayName, attributeName from dgmAttributeTypes where attributeId = ?', array($attr[$i]['valueint']));
                    if (!empty($attrName)) {
                        $attr[$i]['valuestring'] = $attrName[0]['displayname'];
                        $attr[$i]['unitname'] = '';
                    }
                } else if (isset($attr[$i]['unitname']) && (preg_match('/[0-9]=([^ ]+)/', $attr[$i]['unitname']))) {
                    $attr[$i]['valueint'] = floor(empty($attr[$i]['valueint']) ? $attr[$i]['valuefloat'] : $attr[$i]['valueint']);
                    $values = explode(' ', $attr[$i]['unitname']);
                    foreach ($values as $v) {
                        $val = explode('=', $v);
                        if ($val[0] == $attr[$i]['valueint']) {
                            $attr[$i]['valuestring'] = ucfirst($val[1]);
                            break;
                        }
                    }
                    if ($attr[$i]['valuestring'] == 'L') $attr[$i]['valuestring'] = 'Large'; // special case, "Large" is only listed as "l" for some reason
                    $attr[$i]['unitname'] = '';
                } else if (isset($attr[$i]['unitname']) && (($attr[$i]['unitname'] == '%') && (!empty($attr[$i]['valuefloat'])))) {
                    if (($attr[$i]['unitid'] == 108) || ($attr[$i]['unitid'] == 111)) {
                        $attr[$i]['valuefloat'] = ((1 - $attr[$i]['valuefloat']) * 100);
                    } else if ($attr[$i]['unitid'] == 109) {
                        $attr[$i]['valuefloat'] = ($attr[$i]['valuefloat'] - 1) * 100;
                    } else if ($attr[$i]['unitid'] == 127) {
                        $attr[$i]['valuefloat'] = $attr[$i]['valuefloat'] * 100;
                    }
                } else if (isset($attr[$i]['unitid']) && ($attr[$i]['unitid'] == 140)) {
                    $attr[$i]['valueint'] = empty($attr[$i]['valueint']) ? $attr[$i]['valuefloat'] : $attr[$i]['valueint'];
                    $attr[$i]['unitname'] = '';
                }
            }
        }

        $item->getDescription();
        $item->getPricing();
        $item = objectToArray($item, array('DBManager', 'eveDB'));
        $attr = objectToArray($attr, array('DBManager', 'eveDB'));

        return $this->render('info', array('attributes' => $attr, 'item' => $item));
    }

}

?>
