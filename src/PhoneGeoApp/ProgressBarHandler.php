<?php

namespace PhoneGeoApp;
use Symfony\Component\Console\Helper\ProgressBar;


class ProgressBarHandler{

	public $step;

	public $progression;

	public $progressBar;

	public function __construct( $output, $steps, $nbOperations ){

		$this->step = $steps/$nbOperations;
		$this->progression = 0;
		$this->progressBar = new ProgressBar($output, $steps);
		$this->start();

	}


	public function start(){
		$this->progressBar->start();
	}


	public function finish(){
		$this->progressBar->finish();
	}

	public function increment(){
		
		$before = intval($this->progression);
		$this->progression += $this->step;
		$diff = $this->progression - $before;
			
		if( $diff >= 1){
			$this->progressBar->advance($diff);
		}
	}

}