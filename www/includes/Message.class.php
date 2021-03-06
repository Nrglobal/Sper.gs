<?php
/*
 * Message.class.php
 * 
 * Copyright (c) 2014 Andrew Jordan
 * 
 * Permission is hereby granted, free of charge, to any person obtaining 
 * a copy of this software and associated documentation files (the 
 * "Software"), to deal in the Software without restriction, including 
 * without limitation the rights to use, copy, modify, merge, publish, 
 * distribute, sublicense, and/or sell copies of the Software, and to 
 * permit persons to whom the Software is furnished to do so, subject to 
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be 
 * included in all copies or substantial portions of the Software. 
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, 
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF 
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. 
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY 
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, 
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

Class Message
{
    
    /**
     * Database connection object
     * @var db_connection
     */
    private $_pdo_conn;

    /**
     * Site object
     * @var Site
     */
    private $_site;

    /**
     * Message ID
     * @var integer
     */
    private $_message_id;

    /**
     * Message text
     * @var string
     */
    private $_message;

    /**
     * Topic ID for the message
     * @var integer
     */
    private $_parent_id;

    /**
     * Message revision number
     * @var integer
     */
    private $_revision_no;

    /**
     * User ID of the message poster
     * @var [type]
     */
    private $_user_id;

    /**
     * Username of the message poster
     * @var string
     */
    private $_username;

    /**
     * Title of the parent object (ie link, topic, etc)
     * @var string
     */
    private $_title;

    /**
     * State of the message, 0 for existing, 1 for deleted, 
     * 2 for deleted by moderator. 
     * @var integer
     */
    private $_state;

    private $_anonymous;

    private $_type;
    /**
     * Create a new Message Object
     * 
     * @param Site    $site        Site object to get globals variables
     * @param integer $message_id  ID of the message to retrieve
     * @param integer $revision_no Revision number of the message
     * @param integer $type        Message type (0 for topic, 1 for link, 2 for pm)
     */
    public function __construct(Site $site, $message_id, $revision_no = null, $type = 1)
    {
        $this->_pdo_conn = ConnectionFactory::getInstance()->getConnection();
        $this->_site = $site;

        if ($type == 1) {
            $this->_table = "Topics";
            $this->_column = "topic_id";
        } elseif ($type == 2) {
            $this->_table = "Links";
            $this->_column = "link_id";
        }

        $this->_loadMessage($message_id, $revision_no);
    }

    /**
     * Load message data
     * @param  integer   $message_id  Message ID
     * @param  integer   $revision_no Revision number
     * @throws exception              If the provided Message ID and revision number do not exist
     * @return void              
     */
    private function _loadMessage($message_id, $revision_no, $type = 1)
    {
        
        $data_loadMessage = array(
            "message_id_post" => $message_id,
            "message_id" => $message_id,
        );

        if (!is_null($revision_no)) {
            $revision_sql = "AND Messages.revision_no = :revision_no";
            $data_loadMessage["revision_no"] = $revision_no;
        } else {
            $revision_sql = "";
        }

        $sql = "SELECT Messages.link_id, Messages.topic_id,  Messages.message_id, Messages.revision_no, Messages.user_id, 
            Messages.message, Messages.deleted, Messages.type, Users.username, $this->_table.title, 
            (SELECT Messages.posted FROM Messages WHERE Messages.message_id = :message_id_post
                AND Messages.revision_no = 0) as posted
        FROM Users
        LEFT JOIN Messages USING(user_id)
        LEFT JOIN $this->_table USING($this->_column)
        WHERE Messages.message_id = :message_id $revision_sql
        ORDER BY Messages.revision_no DESC LIMIT 1";

        $statement_loadMessage = $this->_pdo_conn->prepare($sql);
        $statement_loadMessage->execute($data_loadMessage);

        if ($statement_loadMessage->rowCount() == 1) {
            $results = $statement_loadMessage->fetch();
            $statement_loadMessage->closeCursor();

            $this->_message_id = $results['message_id'];
            $this->_user_id = $results['user_id'];
            $this->_username = $results['username'];
            $this->_title = $results['title'];
            $this->_state = $results['deleted'];
            $this->_posted = $results['posted'];

            $this->_parent_id = $results['topic_id'] > $results['link_id'] ? $results['topic_id'] : $results['link_id'];
            $this->_type = $results['topic_id'] > $results['link_id'] ? 't' : 'l';
            $this->_revision_no = $results['revision_no'];

            $sql_checkAnon = "SELECT TopicalTags.title  FROM Tagged 
                LEFT JOIN TopicalTags USING(tag_id)    
                WHERE data_id = ".$this->_parent_id."
                AND Tagged.type = 1 AND TopicalTags.title = 'Anonymous'";
                $statement_checkAnon = $this->_pdo_conn->query($sql_checkAnon);
                if (count($statement_checkAnon->fetchAll()) > 0) {
                    $this->_anonymous = true;
                }
            if ($this->_anonymous == true) {
                $sql_getUsers = "SELECT DISTINCT(user_id)
                    FROM Messages WHERE topic_id = ".$this->_parent_id."
                    ORDER BY message_id";
                $statement_getUsers = $this->_pdo_conn->query($sql_getUsers);
                $results_getUsers = $statement_getUsers->fetchAll(PDO::FETCH_COLUMN, 0);
                $human = array_search($this->_user_id, $results_getUsers) +1;
                $this->_username = "Human #".$human;
                $this->_user_id = $human * -1;
            }

            // Replace message text if the message has been deleted
            if ($this->_state == 0) {
                $this->_message = $results['message'];
            } elseif ($this->_state == 1) {
                $this->_message = $this->_site->getMessage("message_deleted");
            } elseif ($results['deleted'] == 2) {
                $this->_message = $this->_site->getMessage("message_deleted_moderator");
            }
            
        } else {
            throw new Exception('Message does not exist');
        }
    }

    /**
     * Delete a message from a topic
     * @param  integer $action       1 if the creator is deleting the message, 2 if its a moderator
     * @param  integer $moderator_id ID of the moderator deleting the message
     * @param  string  $reason       Reason for deleting the message if a moderator
     * @return void               
     */
    public function delete($action, $moderator_id = null, $reason = null)
    {
        $sql_delete = "UPDATE Messages SET deleted = :deleted WHERE message_id = ".$this->_message_id;
        $statement_delete = $this->_pdo_conn->prepare($sql_delete);
        $statement_delete->bindParam("deleted", $action);
        $statement_delete->execute();
        $statement_delete->closeCursor();

        if ($action == 2) {
            $sql_modDelete = "INSERT INTO DisciplineHistory 
                (user_id, mod_id, message_id, action_taken, description, date)
                VALUES (".$this->_user_id.", $moderator_id, ".$this->_message_id.", 
                'Message Deleted', :description, ".time().")";
            $statement_modDelete = $this->_pdo_conn->prepare($sql_modDelete);
            $statement_modDelete->bindParam("description", $reason);
            $statement_modDelete->closeCursor();
            $this->_message = $this->_site->getMessage("message_deleted_moderator");
        } else {
            $this->_message = $this->_site->getMessage("message_deleted");
        }
    }

    /**
     * Get the revision history of a message
     * @return array Message revision history
     */
    public function getRevisions()
    {
        $table_name = "Messages";

        $sql = "SELECT revision_no, posted
            FROM $table_name WHERE message_id = ".$this->_message_id.
            " ORDER BY revision_no DESC";
        $statement = $this->_pdo_conn->query($sql);
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $results;
    }

    /**
     * Get the message content
     * @param  boolean $quote True if the content will be used as a quote
     * @return string         The message
     */
    public function getMessage($quote = false)
    {
        if ($quote == true) {
            $message = explode("\n---", $this->_message);
        
            if (count($message > 1)) {
                array_pop($message);
                $message = trim(implode("---", $message));
            } else {
                $message = $this->_message;
            }

            $quote = "<quote msgid=".$this->_type.",".$this->_parent_id.","
                .$this->_message_id."@".$this->_revision_no.">";
            $quote .= $message;
            $quote .= "</quote>";
            $message = $quote;
        } else {
            $message = $this->_message;
        }
        return $message;
    }

    public function editMessage($message)
    {
        $sql = "SELECT MAX(revision_no) as revision_no FROM Messages
            WHERE Messages.message_id = :message_id AND Messages.user_id = :user_id";
        $data = array(
            "message_id" => $this->_message_id,
            "user_id" => $this->_user_id
        );
        $statement = $this->_pdo_conn->prepare($sql);
        $statement->execute($data);
        $row = $statement->fetch();
        $statement->closeCursor();
        if ($statement->rowCount() == 1) {
            // Provided message ID exists
            $revision_number = $row[0] + 1;
            $sql2 = "INSERT INTO Messages (message_id, user_id, $this->_column, message, 
                revision_no, posted) 
                VALUES(:message_id, :user_id, :$this->_column, :message, 
                $revision_number, ".time().")";
            $data2 = array(
                "message_id" => $this->_message_id,
                "user_id" => $this->_user_id,
                "$this->_column" => $this->_parent_id,
                "message" => $message
            );
            $statement2 = $this->_pdo_conn->prepare($sql2);
            return $statement2->execute($data2);
        } else {
            // Provided message ID does not exist
            return false;
        }
    }

    /**
     * Return the message state, 0 if the message still exists,
     * 1 if it has been deleted, 2 if it has been deleted by a 
     * moderator
     * 
     * @return integer The message state
     */
    public function getState()
    {
        return $this->_state;
    }

    /**
     * Title of the parent object for which the message belongs
     * (ie topic, link, etc)
     * 
     * @return string Title
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * Get the username of the message poster
     * 
     * @return string Username
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Get the user ID of the message poster 
     * 
     * @return integer User ID
     */
    public function getUserId()
    {
        return $this->_user_id;
    }

    /**
     * Get the timestamp of when the message was posted
     * 
     * @return integer Unix timestamp
     */
    public function getPosted()
    {
        return $this->_posted;
    }

    /**
     * Get the current revision number for a given message
     * 
     * @return integer Revision number
     */
    public function getRevisionId()
    {
        return $this->_revision_no;
    }

    public function getParentId()
    {
        return $this->_parent_id;
    }
}
