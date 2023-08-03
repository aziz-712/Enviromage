<?php

/**
 * @file
 * runs composer performance check
 */

namespace Drupal\enviromage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\enviromage\RunComposerCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;

class RunComposerCommandForm extends FormBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * @var \Drupal\enviromage\Controller\EnviromageController
   */
  protected $RunComposerCommand;

  public function __construct(Renderer $renderer, RunComposerCommand $RunComposerCommand) {
    $this->renderer = $renderer;
    $this->RunComposerCommand = $RunComposerCommand;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('enviromage.run_composer_command'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'run_composer_command';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditable() {
    return [
      'enviromage.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('enviromage.settings');

    $command = $config->get('composer_command');

    $form['message1'] = [
      '#type' => 'markup',
      '#markup' => "<p>Run the following command:
                    <code>$command</code>.
                    This command simulate a composer update without applaying it.
                    Also, it profiles memory and time usage.</p>",
    ];

    $form['customize_command'] = [
      '#type' => 'details',
      '#title' => $this->t('Customize your command'),
      '#open' => TRUE,
    ];

    $form['customize_command']['version_constraint'] = [
      '#type' => 'textfield',
      '#title' => t('Choose the version constraint you want to update to'),
      '#description' => t('Enter your text here.'),
    ];

    $moduleDirectories = \Drupal::service('module_handler')->getModuleDirectories();
    $moduleNames = [];
    $moduleNames[''] = '-- all Drupal Modules --';
    foreach ($moduleDirectories as $moduleName => $path) {
      unset($path);
      $moduleNames[$moduleName] = $moduleName;
    }

    $form['customize_command']['package'] = [
      '#type' => 'select',
      '#title' => t('Choose which package to evaluate its update'),
      '#description' => t('choose just one'),
      '#options' => $moduleNames,
      '#default_value' => $config->get('modules_list'),
    ];

    $form['customize_command']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Customize Command'),
    ];

    $form['message2'] = [
      '#type' => 'markup',
      '#markup' => '<div id="result-message-composer"></div>',
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['run_composer'] = [
      '#type' => 'button',
      '#value' => $this->t('Run Composer Command'),
      '#ajax' => [
        'callback' => '::runComposerCommand',
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for PHP benchmark AJAX.
   */
  public function runComposerCommand(array &$form, FormStateInterface $form_state): AjaxResponse {
    $result = $this->RunComposerCommand->get_update_info_about_enabled_modules();
    $markup = [
      '#theme' => 'composer_command',
      '#result' => $result,
    ];
    $response = new AjaxResponse();
    $response->addCommand(
      new HtmlCommand(
        '#result-message-composer',
        $this->renderer->render($markup)
      )
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // begin version constraint

//    $version_constraint = $form_state->getValue('version_constraint');
//    $package = $form_state->getValue('package');
//
//    $versionParser = new VersionParser();
//
//    try {
//      // The parseConstraints() method will throw an exception if the version constraint is invalid.
//      $versionParser->parseConstraints($version_constraint);
//      // If the version constraint is valid, you can proceed with your code here.
//      // For example, you can install the package using Composer or perform other actions.
//      \Drupal::messenger()->addMessage(t('The version constraint is valid.'));
//      $command = "composer update $package:$version_constraint --dry-run --profile";
//
//    } catch (\UnexpectedValueException $e) {
//      // Handle the case when the version constraint is invalid.
//      // For example, display an error message or log the error.
//      // You can also check the exception message for more details on why the constraint is invalid.
//      $errorMessage = $e->getMessage();
//
//      if ($version_constraint === '') {
//        \Drupal::messenger()->addMessage(t('No version constraint is specified'));
//        $command = "composer update $package --dry-run --profile";
//      } else {
//        \Drupal::messenger()->addError(t('The version constraint is not valid.'));
//        $command = '';
//      }
//
//    }
//
//    $this->config('enviromage.settings')
//      ->set('composer_command', $command)
//      ->save();

    // end version constraint

    // begin insert into database

    //    $submitted_email = $form_state->getValue('email');
//    $this->messenger()->addMessage(t("Te form is working! You entered @entry.",
//      ['@entry' => $submitted_email]));
    try {
      // Begin Phase 1: initiate variables to save.

      // Get current user ID.
      $uid = \Drupal::currentUser()->id();

      // Obtain values as entered into the Form.
      $email = $form_state->getValue('email');

      $current_time = \Drupal::time()->getRequestTime();
      // End Phase 1

      // Begin Phase 2: save the values to the database

      // Start to build a query builder object $query.
      // https://www.drupal.org/docs/8/api/database-api/insert-queries
      $query = \Drupal::database()->insert('rsvplist');

      // Specify the fields that the query will insert into.
      $query->fields([
        'uid',
        'nid',
        'mail',
        'created',
      ]);

      // Set the values of the fields we selected.
      // Note that they must be in the same order as we defined them
      // in the $query->fields([...]) above.
      $query->values([
        $uid,
        $nid,
        $email,
        $current_time,
      ]);

      // Execute the query!
      // Drupal handle the exact syntax of the query automatically!
      $query->execute();
      // End Phase 2

      // Begin Phase 3: Display a success message

      // Provide the form submitter a nice message.
      \Drupal::messenger()->addMessage(
        t('Thank you for your RSVP, you are on the list for the event!')
      );
      // End Phase 3
    } catch (\Exception $e) {
      \Drupal::messenger()->addError(
        t('Unable to save RSVP settings at this time due to database error.
          Please try again.')
      );
    }

  }



}
