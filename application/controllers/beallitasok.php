<?
use PortalManager\Portal;

class beallitasok extends Controller {
		function __construct(){
			parent::__construct();
			parent::$pageTitle = 'Beállítások';

			// SEO Információk
			$SEO = null;
			// Site info
			$SEO .= $this->view->addMeta('description','');
			$SEO .= $this->view->addMeta('keywords','');
			$SEO .= $this->view->addMeta('revisit-after','3 days');

			// FB info
			$SEO .= $this->view->addOG('type','website');
			$SEO .= $this->view->addOG('url','');
			$SEO .= $this->view->addOG('image','');
			$SEO .= $this->view->addOG('site_name','');

			$this->view->SEOSERVICE = $SEO;
		}

		function __destruct(){
			// RENDER OUTPUT
				parent::bodyHead();					# HEADER
				$this->view->render(__CLASS__);		# CONTENT
				parent::__destruct();				# FOOTER
		}
	}

?>
