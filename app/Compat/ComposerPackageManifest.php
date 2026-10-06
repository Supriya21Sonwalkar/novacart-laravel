<?php
namespace App\Compat;
// Laravel 7.2 predates Composer 2's installed.json envelope. Keep framework
// vendor files untouched and normalize that envelope only in this adapter.
class ComposerPackageManifest extends \Illuminate\Foundation\PackageManifest {
 public function build(){
  $file=$this->vendorPath.'/composer/installed.json';$raw=$this->files->exists($file)?json_decode($this->files->get($file),true):[];$packages=$raw['packages']??$raw;
  $ignored=$this->packagesToIgnore();$all=in_array('*',$ignored,true);$manifest=[];
  foreach($packages as $package){if(!isset($package['name']))continue;$config=$package['extra']['laravel']??[];$manifest[$this->format($package['name'])]=$config;$ignored=array_merge($ignored,$config['dont-discover']??[]);}
  foreach($manifest as $name=>$config)if($all||in_array($name,$ignored,true)||!$config)unset($manifest[$name]);
  $this->write($manifest);
 }
}
