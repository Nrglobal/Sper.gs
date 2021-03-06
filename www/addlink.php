<?php
/*
 * addlink.php
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
 
require "includes/init.php";
require "includes/Link.class.php";
require "includes/Parser.class.php";
require "includes/Tag.class.php";

// Check authentication
if ($auth === true) {
    // Edit existing link
    $parser = new Parser();
    $tag = new Tag($authUser->getUserId());
    if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
        $csrf->setPageSalt("editLink".$authUser->getUserId().$_GET['edit']);
        $link_edit = new Link($authUser, $parser, $tag, $_GET['edit']);
        //$link_edit_data = $link_edit->getLink();
        // Check to make sure the link
        // creator is the one editing the link
        if ($link_edit->getLinkUserId() == $authUser->getUserId()) {
            // Assign template variables
            $smarty->assign("title", htmlentities($link_edit->getTitle()));
            $smarty->assign(
                "description",
                htmlentities($link_edit->getDescription())
            );
            $smarty->assign("lurl", htmlentities($link_edit->getUrl()));
            $smarty->assign("link_edit", true);
            $smarty->assign("link_id", $link_edit->getLinkId());
            //$tags = $link_edit->getLinkTags($link_edit_data['link_id']);

            $tags = $tag->getObjectTags($_GET['edit'], 2);
            $tag_list = array();
            foreach ($tags as $tag) {
                if (!is_null($tag['tag_id'])) {
                    $tag_list[] = $tag['tag_id'].":".$tag['title'];
                }
            }

            $smarty->assign("tags", implode(",", $tag_list));
            if (isset($_POST['token'])) {
                // Validate provided data
                $error_msg = "";
                if (isset($_POST['lurl'])) {
                    if (!validateURL($_POST['lurl'])) {
                        // Make sure URL is valid
                        $error_msg = "Please enter a valid URL<br />";
                    }
                    $smarty->assign("lurl", htmlentities($_POST['lurl']));
                }
                if (isset($_POST['title'])) {
                    // Check title lenght and remove blank
                    // characters from the end.
                    $smarty->assign("title", htmlentities($_POST['title']));
                    if (strlen($_POST['title']) < 5
                        || strlen($_POST['title'] > 80)
                    ) {
                        $error_msg .= "The title must be between 5 and 80<br />";
                    }
                }
                if (isset($_POST['description'])) {
                    $smarty->assign(
                        "description",
                        htmlentities($_POST['description'])
                    );
                    if (strlen($_POST['description']) < 5) {
                        $error_msg .=
                            "Description must be long than 5 characters<br />";
                    }
                }
                if ($error_msg=="") {
                    // Validate anti-CSRF token
                    if ($csrf->validateToken($_POST['token'])) {
                        if (!isset($_POST['lurl'])) {
                            $url = null;
                        } else {
                            $url = $_POST['lurl'];
                        }

                        $tag_edit = explode(",", $_POST['tags']);

                        $new_tags = array();

                        for ($i=0; $i<count($tag_edit); $i++) {
                            $tmp = explode(":", $tag_edit[$i]);
                            if (count($tmp) < 2) {
                                $new_tags[] = $tag_edit[$i];
                            }
                        }

                        $link_edit->updateLink($_POST['title'], $url, $_POST['description'], $new_tags);
                        header("Location: ./linkme.php?l=".$link_edit->getLinkID());
                        exit();
                    } else {
                        $error_msg = "There was a problem processing your request.
                            Please try again";
                    }
                }
                $smarty->assign("error", $error_msg);
            }
        } else {
            include "404.php";
        }
        
    } else {
        $csrf->setPageSalt("addlink");
        // Add new link
        $links = new Link($authUser, $parser, $tag);
        if (isset($_POST['title'])
            && isset($_POST['description'])
            && isset($_POST['token'])
        ) {
            $error_msg = "";
            // Validate provided data
            if (isset($_POST['lurl'])) {
                if (!validateURL($_POST['lurl'])) {
                    $error_msg = "Please enter a valid URL<br />";
                }
                $smarty->assign("lurl", htmlentities($_POST['lurl']));
            }
            if (isset($_POST['title'])) {
                $smarty->assign("title", htmlentities($_POST['title']));
                if (strlen($_POST['title']) < 5 || strlen($_POST['title'] > 80)) {
                    $error_msg .= "The title must be between 5 and 80<br />";
                }
            }
            if (isset($_POST['description'])) {
                $smarty->assign(
                    "description",
                    htmlentities($_POST['description'])
                );
                if (strlen($_POST['description']) < 5) {
                    $error_msg .= "Description must be long than 5 characters<br />";
                }
            }
            if ($error_msg=="") {
                // Validate anti-CSRF token
                if ($csrf->validateToken($_POST['token'])) {
                    $tags = explode(",", $_POST['tags']);
                    $link_id = $links->createLink($_POST['title'], $_POST['lurl'], $_POST['description'], $tags);
                    if ($link_id > 0) {
                        header("Location: ./linkme.php?l=$link_id");
                        exit();
                    } else {
                        $smarty->assign("post_again", $link_id * -1);
                    }
                } else {
                    $error_msg = "There was a problem processing your request. 
                        Please try again";
                }
            }
            $smarty->assign("error", $error_msg);
        }
    }

    // Assign template variables
    $smarty->assign("token", $csrf->getToken());

    // Set template page
    $display = "addlink.tpl";
    $page_title = "Add Link";
    include "includes/deinit.php";
} else {
    include "404.php";
}
