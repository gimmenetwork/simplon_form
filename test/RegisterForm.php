<?php

  class RegisterForm
  {
    /** @var \Simplon\Form\Form */
    protected $formInstance;

    // ##########################################

    public function _construct()
    {
      $this->formInstance = \Simplon\Form\Form::init()
        ->setFormId('myForm')
        ->setUrl('page.php')
        ->setMethod('POST')
        ->setTemplate($this->getTemplatePath())
        ->setElements($this->getElements())
        ->setFollowUps($this->getFollowUps());
    }

    // ##########################################

    /**
     * @return Simplon\Form\Form
     */
    protected function getFormInstance()
    {
      return $this->formInstance;
    }

    // ##########################################

    /**
     * @return bool
     */
    public function validate()
    {
      return $this
        ->_getFormInstance()
        ->isValid();
    }

    // ##########################################

    /**
     * @return string
     */
    public function render()
    {
      return $this
        ->_getFormInstance()
        ->render();
    }

    // ##########################################

    /**
     * @return bool
     */
    public function runFollowUps()
    {
      return $this
        ->_getFormInstance()
        ->runFollowUps();
    }

    // ##########################################

    /**
     * @return string
     */
    protected function getTemplatePath()
    {
      return './RegisterFormTemplate.html';
    }

    // ##########################################

    /**
     * @return array
     */
    protected function getElements()
    {
      $elements = array();

      // username field
      $elements[] = \Simplon\Form\Elements\TextField::init()
        ->setId('username')
        ->setLabel('Username')
        ->addRule('Required')
        ->addRule('Remote', array('type' => 'post', 'url' => 'http://localhost/opensource/server/simplon/simplon_form/test/remote/test-post.php'))
        ->addRule('MaxLength', 15);

      // password field
      $elements[] = \Simplon\Form\Elements\PasswordField::init()
        ->setId('password')
        ->setLabel('Password')
        ->addRule('Required')
        ->addRule('MinLength', 4);

      // password field
      $elements[] = \Simplon\Form\Elements\EmailField::init()
        ->setId('email')
        ->setLabel('Email')
        ->addRule('required')
        ->addRule('Email');

      // tos field
      $elements[] = \Simplon\Form\Elements\CheckboxField::init()
        ->setId('tos')
        ->setLabel('<span id="termstext" class="field legalline  formerror">I agree to the <a href="http://war2glory.com/enterms/" target="_blank">General Terms and Conditions</a>, the <a href="http://war2glory.com/enpp/" target="_blank">Privacy Policy</a> and the <a href="http://war2glory.com/enrules/" target="_blank">Game Rules</a>.</span>')
        ->addRule('Required', NULL, 'This field needs to be checked.');

      return $elements;
    }

    // ##########################################

    /**
     * @return array
     */
    protected function getFollowUps()
    {
      $followUps = array();

      $followUps[] = function ($data)
      {
        echo "FOLLOWUP<br>";
        var_dump($data);
        echo '<hr>';
      };

      return $followUps;
    }
  }