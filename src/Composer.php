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

    /** @var string $bucket Aws bucket name */
    private $vendorsList = array();

    /** @var string $accessKey */
    private $ignoreKey;

    /** @var string $secretKey */
    private $includeKey;

    /** @var string $bucketURL Url of amazon bucket */
    private $ignorePackages = array();

    /**
     * Module initialization
     */
    public function __construct($systemPath, $lockFileName = null)
    {
        $this->systemPath = $systemPath;
        if (isset($lockFileName)) {
            $this->lockFileName = $lockFileName;
        }
    }

    public function addVendor($vendor)
    {
        if (!in_array($vendor, $this->vendorsList)) {
            $this->vendorsList[] = $vendor.'/';
        }
        return $this;
    }

    public function setIgnoreKey($ignoreKey)
    {
        $this->ignoreKey = $ignoreKey;
        return $this;
    }

    public function setIncludeKey($includeKey)
    {
        $this->includeKey = $includeKey;
        return $this;
    }

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
        /** Composer.lock is always in the project root folder */
        $path = $this->$systemPath.$this->composerLockFile;

        // If we have composer configuration file
        if (file_exists($path)) {
            // Read file into object
            $composerObject = json_decode(file_get_contents($path), true);

            $addedPackages = array();
            $requireList = array();
            // Include packages list
            $requireIncludePackages = array();

            // Gather all possible packages
            $packages = array_merge(
                array(),
                isset($composerObject['packages']) ? $composerObject['packages'] : array(),
                isset($composerObject['packages-dev']) ? $composerObject['packages-dev'] : array()
            );

            // Get included packages list
            foreach ($packages as $package) {
                $requirement = $package['name'];
                if (!in_array($requirement, $this->ignorePackages)) {
                    if (!isset($this->ignoreKey)||(!isset($package['extra'][$this->ignoreKey]))) {
                        if (isset($this->includeKey) && isset($package['extra'][$this->includeKey])) {
                            $requireIncludePackages[] = $requirement;
                        } else {
                            if (sizeof($this->vendorsList)) {
                                foreach ($this->vendorsList as $vendor) {
                                    if (strpos($requirement, $vendor) !== false) {
                                        $requireIncludePackages[] = $requirement;
                                        break;
                                    }
                                }
                            } else {
                                $requireIncludePackages[] = $requirement;
                            }
                        }
                    }
                }

            }

            // Create list of included packages with there require modules
            foreach ($packages as $package) {
                $requirement = $package['name'];
                if (in_array($requirement, $requireIncludePackages)) {
                    $requireList[$requirement] = array();
                    if (isset($package['require'])) {
                        foreach ($package['require'] as $subRequirement => $version) {
                            if (in_array($subRequirement, $requireIncludePackages)) {
                                $requireList[$requirement][] = $subRequirement;
                            }
                        }
                    }
                }
            }

            // Set packages rating
            foreach ($requireList as $package => $list) {
                $this->composerRating($package, $addedPackages, $requireList, 1);
            }


            if (sizeof($addedPackages)) {
                // Sort packages rated
                if (arsort($addedPackages)) {
                    return $addedPackages;
                }
            }
        }
        return array();
    }

    /**
     * Recursive function that provide package priority count
     * @param $requirement Current package name
     * @param $addedModules List of packages with rating
     * @param array $require packages list with require packages
     * @param int $current Current rating
     * @param string $parent Parent package
     */
    private function ratingCount($requirement, & $addedPackages, $require = array(), $current = 1, $parent = '')
    {
        // if current package is not added to list
        if (!isset($addedPackages[$requirement])) {
            // set parent rating
            $addedPackages[$requirement] = $current;
        } else {
            // Update package rating
            $addedPackages[$requirement] = $addedPackages[$requirement] + $current;
            // Update package rating
            $current = $addedPackages[$requirement];
        }
        // Iterate requires package
        foreach ($require[$requirement] as $subRequirement) {
            // Check if two package require each other
            if ($parent != $subRequirement) {
                // Update package rating
                $this->ratingCount($subRequirement, $addedPackages, $require, $current, $requirement);
            }
        }

    }
}
