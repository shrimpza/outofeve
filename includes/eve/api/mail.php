<?php
    function mailSort($a, $b) {
        return ($a->sentDate > $b->sentDate) ? -1 : 1;
    }

    class eveMailList {
        var $mail = array();
        var $account = null;
        var $character = null;
        
        function load($account, $character) {
            $this->account = $account;
            $this->character = $character;

            if (count($this->mail) == 0) {
                $data = new apiRequest('char/MailMessages.xml.aspx', array($account->userId,
                                                                           $account->apiKey,
                                                                           $character->characterID),
                                                                     array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $mail) {
                        $this->mail[] = new eveMailMessage($account, $mail);
                    }
                }
            }

            // get list of character and corp names for messages
            if (count($this->mail) > 0) {
                usort($this->mail, 'mailSort');

                $ids = array();
                foreach ($this->mail as $mail) {
                    if (!empty($mail->senderID) && $mail->senderID > 0) {
                        $ids[] = $mail->senderID;
                    }
                    if (!empty($mail->toCorpID) && $mail->toCorpID > 0) {
                        $ids[] = $mail->toCorpID;
                    }
                    $ids = array_merge($ids, $mail->toCharacterIDs);
                }
                $ids = array_unique($ids);
                $data = new apiRequest('eve/CharacterName.xml.aspx', null, array('ids' => implode(',', $ids)));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $name) {
                        for ($i = 0; $i < count($this->mail); $i++) {
                            if ($this->mail[$i]->senderID == (int)$name['characterID']) {
                                $this->mail[$i]->senderName = (string)$name['name'];
                            }
                            if ($this->mail[$i]->toCorpID == (int)$name['characterID']) {
                                $this->mail[$i]->toCorpName = (string)$name['name'];
                            }
                            for ($j = 0; $j < count($this->mail[$i]->toCharacterIDs); $j++) {
                                if ($this->mail[$i]->toCharacterIDs[$j] == (int)$name['characterID']) {
                                    $this->mail[$i]->toCharacterNames[$j] = (string)$name['name'];
                                }
                            }
                        }
                    }
                }
            }
        }

        function getMessage($messageId) {
            $result = false;
            foreach ($this->mail as $m) {
                if ($m->messageID == $messageId) {
                    $data = new apiRequest('char/MailBodies.xml.aspx', array($this->account->userId,
                                                                             $this->account->apiKey,
                                                                             $this->character->characterID),
                                                                       array('version' => 2,
                                                                            'ids' => $m->messageID));
                    if ((!$data->error) && ($data->data)) {
                        foreach ($data->data->result->rowset->row as $mail) {
                            if ((int)$mail['messageID'] == $m->messageID) {
                                $result = new eveMailMessageBody($mail);
                                $result->headers = $m;
                            }
                        }
                    }
                }
            }

            return $result;
        }
    }

    class eveNotificationsList {
        var $notifications = array();
        var $contactNotifications = array();
        var $account = null;
        var $character = null;

        function load($account, $character) {
            if (count($this->notifications) == 0) {
                $data = new apiRequest('char/Notifications.xml.aspx', array($account->userId,
                                                                            $account->apiKey,
                                                                            $character->characterID),
                                                                      array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $notification) {
                        $this->notifications[] = new eveNotification($this->account, $notification);
                    }
                }

                $data = new apiRequest('char/ContactNotifications.xml.aspx', array($account->userId,
                                                                                   $account->apiKey,
                                                                                   $character->characterID),
                                                                             array('version' => 2));
                if ((!$data->error) && ($data->data)) {
                    foreach ($data->data->result->rowset->row as $notification) {
                        $this->contactNotifications[] = new eveNotificationContact($this->account, $notification);
                    }
                }

                // get list of character and corp names for messages
                if (count($this->notifications) > 0) {
                    $ids = array();
                    foreach ($this->notifications as $note) {
                        if ((!empty($note->senderID) && $note->senderID > 0)
                                && ($note->item->itemid == 0)) {
                            $ids[] = $note->senderID;
                        }
                    }
                    $ids = array_unique($ids);
                    $names = new apiRequest('eve/CharacterName.xml.aspx', null, array('ids' => implode(',', $ids)));
                    if ((!$data->error) && ($data->data)) {
                        foreach ($names->data->result->rowset->row as $name) {
                            for ($i = 0; $i < count($this->notifications); $i++) {
                                if ($this->notifications[$i]->senderID == (int)$name['characterID']) {
                                    $this->notifications[$i]->senderName = (string)$name['name'];
                                }
                            }
                        }
                    }
                }
            }

            usort($this->notifications, 'mailSort');
            usort($this->contactNotifications, 'mailSort');
        }
        
         function getNotification($notificationId) {
            $result = false;
            foreach ($this->notifications as $m) {
                if ($m->notificationID == $notificationId) {
                $data = new apiRequest('char/NotificationTexts.xml.aspx', array($this->account->userId,
                                                                                $this->account->apiKey,
                                                                                $this->character->characterID),
                                                                          array('version' => 2,
                                                                                'ids' => $m->notificationID));
                   if ((!$data->error) && ($data->data)) {
                        foreach ($notificationData->data->result->rowset->row as $text) {
                            if ((int)$text['notificationID'] == $m->notificationID) {
                                $result = new eveNotificationText($text);
                                $result->headers = $m;
                            }
                        }
                    }
                }
            }

            return $result;
        }
    }

    class eveMailMessage {
        var $messageID = 0;
        var $senderID = 0;
        var $sentDate = 0;
        var $title = '';
        var $toCorpID = 0;
        var $toCharacterIDs = array();
        var $toListID = 0;
        var $read = false;

        var $senderName = '';
        var $toCorpName = '';
        var $toCharacterNames = array();
        var $toListName = '';

        function eveMailMessage($acc, $mail) {
            $this->messageID = (int)$mail['messageID'];
            $this->senderID = (int)$mail['senderID'];
            $this->sentDate = strtotime((string)$mail['sentDate']) + $acc->timeOffset;
            $this->title = (string)$mail['title'];
            $this->toCorpID = (int)$mail['toCorpOrAllianceID'];
            
            $tmpIds = explode(',', (string)$mail['toCharacterIDs']);
            foreach ($tmpIds as $id) {
                if (!empty($id) && $id > 0) {
                    $this->toCharacterIDs[] = $id;
                }
            }
            $this->toListID = (int)$mail['toListID'];
            $this->read = (int)$mail['read'] > 0;
            if (!isset($this->read) || empty($this->read)) {
                $this->read = true;
            } else {
                $this->read = false;
            }
        }
    }

    class eveMailMessageBody {
        var $messageID = 0;
        var $message = '';

        function eveMailMessageBody($mail) {
            $this->messageID = (int)$mail['messageID'];
            $this->message = (string)$mail;
        }
    }

    class eveNotification {
        var $notificationID = 0;
        var $senderID = 0;
        var $sentDate = 0;
        var $typeID = 0;
        var $read = false;

        var $title = '';

        var $sender = false;
        var $senderName = '';

        function eveNotification($acc, $notification) {
            global $notificationTitles;

            $this->notificationID = (int)$notification['notificationID'];
            $this->senderID = (int)$notification['senderID'];
            $this->sentDate = strtotime((string)$notification['sentDate']) + $acc->timeOffset;
            $this->typeID = (int)$notification['typeID'];
            $this->read = (int)$notification['read'] > 0;
            if (!isset($this->read) || empty($this->read)) {
                $this->read = true;
            } else {
                $this->read = false;
            }

            $this->title = $notificationTitles[$this->typeID];

            $this->sender = eveDB::getInstance()->eveName($this->senderID);
        }
    }

    class eveNotificationText {
        var $notificationID = 0;
        var $text = '';

        function eveNotificationText($notification) {
            $this->notificationID = (int)$notification['notificationID'];
            $this->text = (string)$notification;
        }
    }
    
    class eveNotificationContact {
        var $notificationID = 0;
        var $senderID = 0;
        var $senderName = '';
        var $sentDate = 0;
        var $messageData = '';

        function eveNotificationContact($acc, $notification) {
            $this->notificationID = (int)$notification['notificationID'];
            $this->senderID = (int)$notification['senderID'];
            $this->senderName = (string)$notification['senderName'];
            $this->sentDate = strtotime((string)$notification['sentDate']) + $acc->timeOffset;
            $this->messageData = (string)$notification['messageData'];
        }
    }
?>