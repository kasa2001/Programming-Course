<?php

namespace Controllers;


use Core\Controller;
use Core\DatabaseNullException;
use Lib\Built\Factory\Factory;
use Lib\Built\Get\Get;
use Lib\Built\Post\Post;
use Lib\Built\Post\PostException;
use Lib\Built\Server\Server;
use Lib\Built\Session\SessionException;
use Lib\Built\View\View;

class User extends Controller
{

    public function login()
    {
        $this->view = View::getInstance($this->config);
        $this->view->display("user/login");
    }

    public function registry()
    {
        $this->view = View::getInstance($this->config);
        $this->view->display("user/registry");
    }

    public function confirm()
    {
        $post = new Post();
        $user = new \Models\Tables\User();
        $user->setNick($post->nick);
        $user->setEmail($post->email);
        $user->setPassword($post->password);
        $user->setType(1);

        $model = new \Models\Logic\User();
        $model->registry($user);

        $server = Server::getInstance();

        $server->redirect(204);
    }

    public function add($params)
    {
        try {
            $session = Factory::getSession();

            $user = new \Models\Tables\User();
            $user->setId($session->getDataWithSession('id'));
            $user->setType($session->getDataWithSession('type'));
        } catch (SessionException $e) {
            $server = Server::getInstance();

            $server->redirect(403, null, "Forbidden");
        } finally {
            $id = $params[0];
            $model = new \Models\Logic\User();
            $model->addToCourse($id, $user);
        }

    }

    public function answer($params)
    {
        $id = $params[0];
        $model = new \Models\Logic\User();
        $session = Factory::getSession();
        $user = new \Models\Tables\User();
        try {
            $user->setId($session->getDataWithSession('id'));
            if (isset($params[1])) {
                $limit = $params[1];
                $post = new Post();
                $model->saveAnswer($id, $post->idquestion, $post->answer, $user);
            } else {
                $limit = 0;
            }

            $this->view = View::getInstance();

            $data = $model->getQuestion($id, $limit);
            if ($model->isAsked($id, $user, $data['question']['id']))
                throw new \Exception("Odpowiedziałeś już na pytania", 403);
            $this->view->display("user/answer", $data);
        } catch (DatabaseNullException $e) {
            if ($limit == 0) {
                $this->view->display('user/not');
            } else {
                $this->view->display('user/end');
            }
        } catch (SessionException | PostException $e) {
            $server = Server::getInstance();
            $server->redirect(403, null, "Forbidden");
        }

    }

    public function logout()
    {
        $session = Factory::getSession();
        $session->destroySession();
        $server = Server::getInstance();
        $server->redirect(200, "home/index", "OK");
    }

    public function listing()
    {
        $model = new \Models\Logic\User();
        $table = new \Models\Tables\User();
        $session = Factory::getSession();
        try {
            $table->setId($session->getDataWithSession('id'));
        } catch (SessionException $e) {
            $server = Server::getInstance();
            $server->redirect(403, "Forbidden");
        }

        $courses = $model->getUserCourse($table);
        $this->view = View::getInstance();

        $data = [
            'courses' => $courses
        ];

        $this->view->display("user/listing", $data);
    }

    public function course($params)
    {
        $id = $params[0];
        $session = Factory::getSession();
        $table = new \Models\Tables\User();
        $model = new \Models\Logic\User();
        try {
            $table->setId($session->getDataWithSession('id'));
        } catch (SessionException $e) {
            $server = Server::getInstance();
            $server->redirect(403, "Forbidden");
        }
    }

    public function addCourse()
    {

        try {

            if ($this->session->getDataWithSession('type')  < 1) {
                $this->server->redirect(403, null, "Forbidden");
            }

            $model = new \Models\Logic\User();
            $user = new \Models\Tables\User();
            $user->setId($this->session->getDataWithSession('id'));
            $post = new Post();
            $model->addCourse($user, $post->idcategory);

        } catch (SessionException $e) {
            $this->server->redirect(403, null, "Forbidden");
        } catch (PostException $e) {

        } finally {
            $data['category'] = $model->getCourse();
            $this->view->display('user/addcourse', $data);
        }


    }

    public function change($params)
    {
        $id = $params[0];
        $model = new \Models\Logic\User();

        try {
            $post = new Post();
            $model->change($id, $post->nick, $post->type);
        } catch (PostException $e) {

        } finally {

            $data['user'] = $model->getUser($id);
            $data['params'] = $model->getParams();

            $this->view->display("user/change", $data);
        }
    }

    public function listOfUsers()
    {
        $model = new \Models\Logic\User();
        $data['users'] = $model->getUsers();
        $this->view->display("user/listofuser", $data);

    }

    public function renderReport($params)
    {
        $type = $params[0];
        $selected = $params[1];

        $model = new \Models\Logic\User();
        $data['report'] = $model->getReport($type,$selected);

        $this->view->display("user/render", $data);
    }

    public function addCategory()
    {
        try {

            if ($this->session->getDataWithSession('type')  < 1) {
                $this->server->redirect(403, null, "Forbidden");
            }

            $model = new \Models\Logic\User();
            $user = new \Models\Tables\User();
            $user->setId($this->session->getDataWithSession('id'));
            $post = new Post();
            echo $post->name;
            $model->addCategory($post->name);

        } catch (SessionException $e) {
            $this->server->redirect(403, null, "Forbidden");
        } catch (PostException $e) {

        } finally {

            $this->view->display('user/addcategory', null);
        }
    }

    public function removeUser()
    {
        $get = new Get();

        $model = new \Models\Logic\User();

        $model->removeUser($get->id);

        $this->server->redirect(200);
    }

    public function addQuestion($params)
    {
        $id = $params[0];
        $data = ['id' => $id];
        $model = new \Models\Logic\User();
        try {
            $post = new Post();
            $model->addQuestion($id, $post->quest);
        } catch (PostException $e) {

        } finally {
            $this->view->display('user/addquestion', $data);
        }
    }

    public function addAnswer($params)
    {
        $id = $params[0];
        $data = [
          'id' => $id
        ];
        $model = new \Models\Logic\User();

        try {
            $post = new Post();
            $model->addAnswer($id, $post->name, $post->good);
        } catch (PostException $e) {

        } finally {
            $this->view->display('user/addanswer', $data);
        }
    }

}