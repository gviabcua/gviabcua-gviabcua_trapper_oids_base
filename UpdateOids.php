<?php namespace  Gviabcua\Trapper\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Gviabcua\Trapper\Models\OidBase;
use domDocument;

class UpdateOids extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'trapper:updateids';

    /**
     * @var string The console command description.
     */
    protected $description = 'Get information about ONU';

    /**
     * Execute the console command.
     * @return void
     */
    public function fire()
    {
        $this->output->writeln('Hello world!');
    }
    public function handle()
    {
    	error_reporting(0);
        $main_url = 'http://www.circitor.fr/Mibs/Mibs.php';
		echo "Start".PHP_EOL;
		$main_data = file_get_contents($main_url);
		$dom_main = new domDocument;
		@$dom_main->loadHTML($main_data);
		$dom_main->preserveWhiteSpace = false;
		$urls_main = $dom_main->getElementsByTagName('a');
		$total = $urls_main->length;
		
		echo "Creating URL LIST " . $total.PHP_EOL;
		$ccc = 1;
		$done = 0;
		foreach($urls_main as $url_main){
			try {
				echo $ccc ."/".$done."/".($total - $done)."/".$total;
				if (strlen($url_main->nodeValue) > 5){
					$url = "http://www.circitor.fr/Mibs/Html/".$url_main->nodeValue[0]."/".$url_main->nodeValue.".php";
					echo " - Start URL " . $url;
					$mib_name = str_replace(".php","", end(explode("/",$url)));
					echo PHP_EOL.PHP_EOL.$mib_name.PHP_EOL.PHP_EOL;
					$data = file_get_contents($url);
					if (!$data)continue;
					$dom = new domDocument;
					@$dom->loadHTML($data);
					$dom->preserveWhiteSpace = false;
					$tables = $dom->getElementsByTagName('table');
					foreach ($tables as $table) {
						$trs = $table->getElementsByTagName('tr');
						$temp_array['name'] = null;
						$temp_array['oid'] = null;
						$temp_array['description'] = null;
						foreach ($trs as $tr){
							$tds = $table->getElementsByTagName('td');
							$i=0;
							foreach ($tds as $td){
								$i++;
								if ($i > 3)continue;
								if ($i == 1){
									$temp_array['name'] = $td->nodeValue;
								}
								if ($i == 2){
									$temp_array['oid'] = $td->nodeValue;
								}if ($i == 3){
									$temp_array['description'] = $td->nodeValue;
								}
							}
						}
						if(preg_match("/^([0-9]+\.){0,}[0-9]+$/", $temp_array['oid'])){
							if (strlen($temp_array['oid']) > 150)continue;
							$update_it = OidBase::firstOrNew(['oid' =>  $temp_array['oid']/*, "mib" => $mib_name*/]);
							$update_it->mib = $mib_name;
							$update_it->oid = $temp_array['oid'];
							$update_it->oid_enterprise = str_replace("1.3.6.1.4.1.", "SNMPv2-SMI::enterprises.", $temp_array['oid']);
							$update_it->name = $temp_array['name'];
							
							switch($temp_array['description']){
								case "OBJECT IDENTIFIER":
								case "OBJECT-TYPE":
									$temp_array['description'] = null;
								break;
								
							}
							try{$temp_array['description'] = mb_convert_encoding($temp_array['description'], 'UTF-8');} catch (Exception $e) {}
							$update_it->description = $temp_array['description'];
							$update_it->updated_at = date("Y-m-d H:i:s");
							$update_it->save();
							echo $update_it->id. ", ";
						}else{
							echo "not oid ";
						}
						
					}
				}
			} catch (Exception $e) {
				echo 'Error: ',  $e->getMessage(), "\n";
			}
			$done ++;$ccc ++;
			echo PHP_EOL;
		}
    }
    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }
    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [ ];
    }

}