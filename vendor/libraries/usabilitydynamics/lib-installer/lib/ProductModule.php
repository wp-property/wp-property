<?php
/**
 *
 */
namespace UsabilityDynamics\Installers {

  use Composer\Installer\LibraryInstaller;
  use Composer\Package\PackageInterface;
  use Composer\Repository\InstalledRepositoryInterface;

  /**
   * Class ProductModule
   *
   * @package UsabilityDynamics\Installers
   */
  class ProductModule extends LibraryInstaller {

    /**
     * Our supported object types
     *
     */
    protected $supported = array(
      'wordpress-package',
      'wordpress-module'
    );

    /**
     * Our supported install locations.
     *
     */
    protected $locations = array(
      'wordpress-module'  => 'modules/{name}',
      'wordpress-package' => 'packages/{name}'
    );

    /**
     * Our install method overrides the parent class' method so that we can duplicate repos
     * when there are multiple versions needed
     *
     */
    public function install( InstalledRepositoryInterface $repo, PackageInterface $package ) {
      // print "Installing::" . $package->getPrettyName() . "::" . $package->getPrettyVersion() . " (ProductModule)\r\n";
      return parent::install( $repo, $package );
    }

    /**
     *
     * @todo May want to add a naming convention enforcement, e.g. "wp-module-{}" for modules.
     * {@inheritDoc}
     */
    public function getPackageBasePath( PackageInterface $package ) {

      $_split       = strpos( $package->getPrettyName(), '/' );
      $vendor_name  = substr( $package->getPrettyName(), 0, $_split );
      $package_name = substr( $package->getPrettyName(), ( $_split + 1 ) );

      $extra = $package->getExtra();

//      die( '<pre>' . print_r( $package->getPrettyName(), true ) . '</pre>' );
//      die( '<pre>' . print_r( $package->getDistType(), true ) . '</pre>' );
//      die( '<pre>' . print_r( $package->getDistUrl(), true ) . '</pre>' );
//      die( '<pre>' . print_r( $extra, true ) . '</pre>' );
//      die( '<pre>' . print_r( get_class_methods(  $package ), true ) . '</pre>' );

      if (!empty($extra['installer-name'])) {
        $package_name = $extra['installer-name'];
      }

      if( isset( $this->vendorDir ) && $this->vendorDir ) {
        $install_path = dirname( $this->vendorDir ) . '/' . $this->locations[ $package->getType() ];
        $install_path = str_ireplace( '{vendor}',   $vendor_name,           $install_path );
        $install_path = str_ireplace( '{name}',     $package_name,          $install_path );
        $install_path = str_ireplace( '{version}',  $package->getVersion(), $install_path );
        return $install_path;
      }

      return $package_name;

    }

    /**
     * Returns which object types we support
     *
     */
    public function supports( $packageType ) {
      return in_array( $packageType, (array) $this->supported );
    }

  }

}