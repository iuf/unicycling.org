<?php
namespace iuf\unicycling\app;

use keeko\framework\foundation\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Iuf Unicycling.org
 *
 * @license MIT
 * @author gossi
 */
class UnicyclingApplication extends AbstractApplication {

	/**
	 * @param Request $request
	 * @param string $path
	 */
	public function run(Request $request) {
		$kernel = $this->getServiceContainer()->getKernel();
		$account = $this->getServiceContainer()->getModuleManager()->load('keeko/account');
		$trixionary = $this->getServiceContainer()->getModuleManager()->load('gossi/trixionary-client');

		$widget = $account->loadAction('account-widget', 'html');
		$widget = $kernel->handle($widget, $request);

		$this->getPage()->setDefaultTitle('IUF Trixionary');
		$this->getPage()->setTitlePrefix('IUF Trixionary:');

		try {
			$routes = $this->generateRoutes();
			$context = new RequestContext($this->getBaseUrl());
			$matcher = new UrlMatcher($routes, $context);

			$match = $matcher->match($this->getDestination());
			$route = $match['_route'];
			$action = null;

			switch ($route) {
				case 'account':
				case 'account-index':
					$action = $account->loadAction('account', 'html');
					$action->setBaseUrl($this->getBaseUrl() . '/account');
					$action->setDestination(str_replace($action->getBaseUrl(), '', $request->getUri()));
					break;

				case 'trixionary':
				default:
					$action = $trixionary->loadAction('trixionary');
					$action->setBaseUrl($this->getBaseUrl() . '/trixionary');
					$action->setDestination($this->getDestination());
					break;
			}

			$kernel = $this->getServiceContainer()->getKernel();
			$response = $kernel->handle($action, $request);

			if ($response instanceof RedirectResponse) {
				return $response;
			}

			$main = $response->getContent();
		} catch (PermissionDeniedException $e) {
			$main = 'Permission Denied';
		} catch (\Exception $e) {
			$main = 'Error: ' . $e->getMessage();
		}


		$response = new Response();
		$response->setContent($this->render('/iuf/unicycling.org/templates/main.twig', [
			'account_widget' => $widget->getContent(),
			'main' => $main
		]));

		return $response;
	}

	/**
	 *
	 * @return RouteCollection
	 */
	private function generateRoutes() {
		$routes = new RouteCollection();
		$routes->add('account-index', new Route('/account'));
		$routes->add('account', new Route('/account/{suffix}', ['suffix' => ''], ['suffix' => '.*']));
		$routes->add('trixionary', new Route('/trixionary/{suffix}', ['suffix' => ''], ['suffix' => '.*']));

		return $routes;
	}
}
