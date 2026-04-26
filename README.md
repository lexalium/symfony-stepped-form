# Symfony Stepped Form

[![PHPUnit, PHPCS, PHPStan Tests](https://github.com/lexalium/symfony-stepped-form/actions/workflows/tests.yml/badge.svg)](https://github.com/lexalium/symfony-stepped-form/actions/workflows/tests.yml)

The package is based on the [HTTP Stepped Form](https://github.com/lexalium/http-stepped-form) and built for
a Symfony framework.

Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
5. [License](#license)

## Requirements

- **PHP:** >=8.1
- **Symfony:** ^6.4 || ^7.4 || ^8.0

## Installation

Via Composer

```bash
composer require lexal/symfony-stepped-form
```

## Configuration

Register the bundle in `config/bundles.php` if not using Symfony Flex, and create `config/packages/stepped_form.yaml` for configuration options (renderer, redirector, event_dispatcher, exception_normalizers, and forms).

Example (manual bundles.php registration):

```php
<?php
return [
    Lexal\SymfonySteppedForm\SteppedFormBundle::class => ['all' => true],
];
```

Example:

```yaml
stepped_form:
  renderer: Lexal\SymfonySteppedForm\Renderer\Renderer
  redirector: Lexal\SymfonySteppedForm\Routing\Routing
  event_dispatcher: Lexal\SymfonySteppedForm\Event\Dispatcher\EventDispatcher
  forms:
    customer_create:
      form_builder: Lexal\SymfonySteppedForm\CreateFormBuilder
      form_settings: Lexal\SymfonySteppedForm\CreateFormSettings
      storage:
        class: Lexal\SymfonySteppedForm\Storage\SessionStorage
        arguments: { $namespace: 'customer-create' }
      session_key_storage:
        class: Lexal\SymfonySteppedForm\Storage\SessionSessionKeyStorage
        arguments: { $namespace: 'customer-create' }
    customer_edit:
      steps:
        first: Lexal\SymfonySteppedForm\Step\Step1
        second: Lexal\SymfonySteppedForm\Step\Step2
      form_settings: Lexal\SymfonySteppedForm\EditFormSettings
      storage: Lexal\SymfonySteppedForm\Storage\SessionStorage
      session_key_storage: Lexal\SymfonySteppedForm\Storage\SessionSessionKeyStorage
```

Where:

1. `renderer` - contains Renderer class or service alias that translates step's template definition
   into the response. Must implement `Lexal\HttpSteppedForm\Renderer\RendererInterface`;
2. `redirector` - contains Redirector class or service alias that redirects user between form
   steps. Must implement `Lexal\HttpSteppedForm\Routing\RedirectorInterface`;
3. `event_dispatcher` - contains Event Dispatcher class or service alias that dispatches form events.
   Must implement `Lexal\SteppedForm\EventDispatcher\EventDispatcherInterface`;
4. `forms` - contains form definitions. Key is the form name. Form is available in the Symfony container as
   `stepped_form.form.<form_name>`. When only one form registered, it is available via interface
   `Lexal\SteppedForm\SteppedFormInterface`.
   Form definition contains:
   - `form_builder` _(required when `steps` is not provided)_ - contains FormBuilder class or service alias that builds form (only when it has dynamic
     steps). Must implement `Lexal\SteppedForm\FormBuilder\FormBuilderInterface`;
   - `steps` _(required when `form_builder` is not provided)_ - contains an array of step class or service aliases
     that implements `Lexal\SteppedForm\Step\StepInterface` (only when it has static steps);
   - `form_settings` - contains FormSettings class or service alias that provides form settings. Must
     implement `Lexal\SteppedForm\FormSettings\FormSettingsInterface`;
   - `storage` - contains Storage class or service alias that provides form storage. Must implement
     `Lexal\SteppedForm\Storage\StorageInterface`;
   - `session_key_storage` _(optional)_ - class or service id to use for storing a per-user session key that identifies
     which form session the current user is working with. Default: `Lexal\\SteppedForm\\Form\\Storage\\NullSessionKeyStorage`
     (no per-user session key). Provide an implementation (for example, a session-backed storage) when you need to isolate
     multiple concurrent form sessions per user or to resume a specific form session.
     Must implement `Lexal\SteppedForm\Storage\SessionKeyStorageInterface`;

## Usage

1. Configure your form settings.
   ```php
    use Lexal\HttpSteppedForm\Settings\FormSettingsInterface;
    use Lexal\SteppedForm\Step\StepKey;

    final class FormSettings implements FormSettingsInterface
    {
        public function getStepUrl(StepKey $key): string
        {
            // return step URL
        }

        public function getUrlBeforeStart(): string
        {
            // returns a URL to redirect to when there is no previously renderable step
        }

        public function getUrlAfterFinish(): string
        {
            // return a URL to redirect to when the form was finishing
        }
    }
   ```
2. Declare form definitions in YAML or XML _(deprecated since Symfony 7.4)_.
3. Inject and use the form in your controller. Form is available in the Symfony container as `stepped_form.form.<form_name>`.

   **CustomerController.php**
   ```php
   use Lexal\HttpSteppedForm\SteppedFormInterface;
   use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

   final class CustomerController extends AbstractController
   {
       public function __construct(private readonly SteppedFormInterface $form)
       {
       }

       // POST /customers
       public function start(): Response
       {
           return $this->form->start(new Customer(), /* nothing or customer id to split different sessions */);
       }

       // GET /customers/step/{step-key}
       public function render(string $key): Response
       {
           return $this->form->render($key);
       }

       // POST /customers/step/{step-key}
       public function handle(Request $request, string $key): Response
       {
           return $this->form->handle($key, $request);
       }

       // POST /customers/cancel
       public function cancel(): Response
       {
           return $this->form->cancel($this->generateUrl('customers'));
       }
   }
   ```

## License

Symfony Stepped Form is licensed under the MIT License. See [LICENSE](LICENSE) for the full license text.
