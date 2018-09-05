<?php namespace Academe\LaravelDkim;

/**
 * Notes:
 * I am not sure if this is the best way to do this.
 * What we want to do, is change the namespace of the "Mailer" that is regisered. To do that,
 * we replicate the entire core register method with no changes, except we are running it in
 * a new namespace. Ideally I would want to run the core method but in the local namespace.
 * Not sure if reflection can do this? Will investigate.
 * I've posed this question here:
 * http://stackoverflow.com/questions/21498618/can-i-execute-a-parent-method-in-the-namespace-of-a-child
 *
 * This method does not support chaining of signing packages. We extend the core mail service
 * to add the signing, and ignore the needs of other packages that may want to add their own
 * signing to the messages at this level.
 */

use Illuminate\Mail\MailServiceProvider as CoreMailServiceProvider;

class MailServiceProvider extends CoreMailServiceProvider {
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('mailer', function ($app) {
            $this->registerSwiftMailer();

            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            if ($app->bound('queue')) {
                $mailer->setQueue($app['queue']);
            }

            if (method_exists($this, 'setMailerDependencies')) {
                $this->setMailerDependencies($mailer, $app);
            }

            // If a "from" address is set, we will set it on the mailer so that all mail
            // messages sent by the applications will utilize the same "from" address
            // on each one, which makes the developer's life a lot more convenient.
            $from = $app['config']['mail.from'];

            if (is_array($from) && isset($from['address'])) {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            $to = $app['config']['mail.to'];

            if (is_array($to) && isset($to['address'])) {
                $mailer->alwaysTo($to['address'], $to['name']);
            }

            return $mailer;
        });
	}
}

