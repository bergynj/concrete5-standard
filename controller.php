<?php 
namespace Concrete\Package\Standard;

use Concrete\Core\Package\Package;
use Concrete\Core\Page\Theme\Theme;

use Page;
use Core;

use FileImporter;
use Concrete\Core\Backup\ContentImporter;

use PageType;
use FileList;
use PageList;
use StackList;

defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package  {

	protected $pkgHandle = 'standard';
    
	protected $appVersionRequired = '5.7.4';
	protected $pkgVersion = '1.0';
	protected $pkg;
    
    protected $pkgAllowsFullContentSwap = true;

	public function getPackageDescription() {
		return t("Revert Site Content to Standard Base Install.");
	}

	public function getPackageName() {
		return t("Standard Content");
	}
	
	public function install($data = array()) {

        $pkg = parent::install();
        
        // Set Theme
        // Theme::setThemeHandle($themeHandle);
        Theme::setThemeHandle('elemental');

	}

    public function swapContent($options) {

        if ($this->validateClearSiteContents($options)) {
            \Core::make('cache/request')->disable();

            $pl = new PageList();
            $pages = $pl->getResults();
            foreach ($pages as $c) $c->delete();

            $fl = new FileList();
            $files = $fl->getResults();
            foreach ($files as $f) $f->delete();

            // clear stacks
            $sl = new StackList();
            foreach ($sl->get() as $c) $c->delete();

            $home = Page::getByID(HOME_CID);
            $blocks = $home->getBlocks();
            foreach ($blocks as $b) $b->deleteBlock();

            $pageTypes = PageType::getList();
            foreach ($pageTypes as $ct) $ct->delete();

            $path = $this->getPackagePath();

            // Import Files
            if (is_dir($path . '/files')) {
                $ch = new ContentImporter();
                $computeThumbnails = true;
                if ($this->contentProvidesFileThumbnails()) {
                    $computeThumbnails = false;
                }
                $ch->importFiles($path . '/files', $computeThumbnails);
            }

            // Install the starting point.
            if (is_file($path . '/content.xml')) :
                $ci = new ContentImporter();
                $ci->importContentFile($path . '/content.xml');
            endif;
            
            // Restore Cache
            \Core::make('cache/request')->enable();
        }
    }
}
