<?php

namespace dktapps\Uncensor;

use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;
use function file;
use function implode;
use function array_map;
use function preg_replace_callback;
use function str_replace;
use function mb_substr;

class Main extends PluginBase implements Listener{

	private array $words = [];
	private string $regex;

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		if(file_exists($this->getDataFolder() . "profanity_filter.wlist")){
			$this->words = file($this->getDataFolder() . "profanity_filter.wlist", FILE_IGNORE_NEW_LINES);
			$this->getLogger()->notice("Loaded word list!");
		}else{
			$this->getLogger()->error("Can't find word list! Please extract it from the game and place it in the plugin's data folder.");
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}

		$this->regex = '/.*?(' . implode('|', array_map('preg_quote', $this->words)) . ').*?/iu';
	}

	private function unfilter(string $message) : string{
		return preg_replace_callback($this->regex, function($matches){
			return str_replace($matches[1], mb_substr($matches[1], 0, 1) . "\x1f" . mb_substr($matches[1], 1), $matches[0]);
		}, $message);
	}

	public function onDataPacketSend(DataPacketSendEvent $event) : void{
        $packets = $event->getPackets();
        foreach ($packets as $pk) {
            if ($pk instanceof TextPacket) {
                if ($pk->type !== TextPacket::TYPE_TRANSLATION) {
                    $pk->message = $this->unfilter($pk->message);
                }
                foreach ($pk->parameters as $k => $param) {
                    $pk->parameters[$k] = $this->unfilter($pk->parameters[$k]);
                }
            }
        }
	}
}
