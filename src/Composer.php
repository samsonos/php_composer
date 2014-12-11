<?php
/**
 * Created by PhpStorm.
 * User: Nikita Kotenko
 * Date: 26.11.2014
 * Time: 14:12
 */
namespace samson\composer;


/**
 * Provide creating sorting list of composer packages
 * @package samson
 */
class Composer
{
    /** @var string Path to current web-application */
    private $systemPath;

    /** @var string  composer lock file name */
    private $lockFileName = 'composer.lock';

    /** @var array List of available vendors */
    private $vendorsList = array();

    /** @var string $ignoreKey */
    private $ignoreKey;

    /** @var string $includeKey */
    private $includeKey;

    /** @var array List of ignored packages */
    private $ignorePackages = array();

    /** @var array List of packages with its priority */
    private $packageRating = array();

    /** @var array  Packages list with require packages*/
    private $packagesList = array();

    /**
     * Module initialization
     * @param $systemPath
     * @param string|null $lockFileName
     */
    public function __construct($systemPath, $lockFileName = 'composer.lock')
    {
        $this->systemPath = $systemPath;
        $this->lockFileName = $lockFileName;
    }

    /**
     *  Add available vendor
     * @param $vendor Available vendor
     * @return $this
     */
    public function addVendor($vendor)
    {
        if (!in_array($vendor, $this->vendorsList)) {
            $this->vendorsList[] = $vendor.'/';
        }
        return $this;
    }


    /**
     * Set name of composer extra parameter to ignore package
     * @param $ignoreKey Name
     * @return $this
     */
    public function setIgnoreKey($ignoreKey)
    {
        $this->ignoreKey = $ignoreKey;
        return $this;
    }

    /**
     * Set name of composer extra parameter to include package
     * @param $includeKey Name
     * @return $this
     */
    public function setIncludeKey($includeKey)
    {
        $this->includeKey = $includeKey;
        return $this;
    }

    /**
     *  Add ignored package
     * @param $vendor Ignored package
     * @return $this
     */
    public function addIgnorePackage($package)
    {
        if (!in_array($package, $this->ignorePackages)) {
            $this->ignorePackages[] = $package;
        }
        return $this;
    }

    /**
     * Create sorted packages list
     * @return array Packages list ('package name'=>'rating')
     */
    public function create()
    {
        // Composer.lock is always in the project root folder
        $path = $this->systemPath.$this->lockFileName;

        // If we have composer configuration file
        if (file_exists($path)) {
            // Read file into object
            $composerObject = json_decode(file_get_contents($path), true);

            // Gather all possible packages
            $packages = array_merge(
                array(),
                isset($composerObject['packages']) ? $composerObject['packages'] : array(),
                isset($composerObject['packages-dev']) ? $composerObject['packages-dev'] : array()
            );

            // Create list of relevant packages with there require packages
            $this->packagesFill($packages);

            // Set packages rating
            foreach ($this->packagesList as $package => $list) {
                $this->ratingCount($package, 1);
            }

            // Sort packages rated
            arsort($this->packageRating);
        }
        return $this->packageRating;
    }

    /**
     * Create list of relevant packages
     * @param $packages Composer lock list of packages
     * @return array List of relevant packages
     */
    private function includeList($packages)
    {
        $includePackages = array();
        foreach ($packages as $package) {
            $requirement = $package['name'];
            if (!$this->isIgnore($package)) {
                if (isset($this->includeKey) && isset($package['extra'][$this->includeKey])) {
                    $includePackages[] = $requirement;
                } elseif ($this->vendorListCheck($package)) {
                        $includePackages[] = $requirement;
                }
            }
        }
        return $includePackages;
    }

    /**
     * Is package ignored
     * @param $package Composer package
     * @return bool - is package ignored
     */
    private function isIgnore($package)
    {
        $isIgnore = false;
        if (in_array($package['name'], $this->ignorePackages)) {
            $isIgnore = true;
        }
        if (isset($this->ignoreKey)&&(isset($package['extra'][$this->ignoreKey]))) {
            $isIgnore = true;
        }
        return $isIgnore;
    }

    /**
     * Check vendor list include
     * @param $package Composer package
     * @return bool - is package include
     */
    private function vendorListCheck($package)
    {
        $include = true;
        if (sizeof($this->vendorsList)) {
            $include = false;
            foreach ($this->vendorsList as $vendor) {
                if (strpos($package['name'], $vendor) !== false) {
                    $include = true;
                    break;
                }
            }
        }
        return $include;
    }

    /**
     * Recursive function that provide package priority count
     * @param $requirement Current package name
     * @param int $current Current rating
     * @param string $parent Parent package
     */
    private function ratingCount($requirement, $current = 1, $parent = '')
    {
        // if current package is not added to list
        if (!isset($this->packageRating[$requirement])) {
            // set parent rating
            $this->packageRating[$requirement] = $current;
        } else {
            // Update package rating
            $this->packageRating[$requirement] = $this->packageRating[$requirement] + $current;
            // Update package rating
            $current = $this->packageRating[$requirement];
        }
        // Iterate requires package
        foreach ($this->packagesList[$requirement] as $subRequirement) {
            // Check if two package require each other
            if ($parent != $subRequirement) {
                // Update package rating
                $this->ratingCount($subRequirement, $current, $requirement);
            }
        }
    }

    /**
     * Fill list of relevant packages with there require packages
     * @param $packages Composer lock file object
     */
    private function packagesFill($packages)
    {
        // Get included packages list
        $includePackages = $this->includeList($packages);

        // Create list of relevant packages with there require packages
        foreach ($packages as $package) {
            $requirement = $package['name'];
            if (in_array($requirement, $includePackages)) {
                $this->packagesList[$requirement] = array();
                if (isset($package['require'])) {
                    foreach (array_keys($package['require']) as $subRequirement) {
                        if (in_array($subRequirement, $includePackages)) {
                            $this->packagesList[$requirement][] = $subRequirement;
                        }
                    }
                }
            }
        }
    }
}
