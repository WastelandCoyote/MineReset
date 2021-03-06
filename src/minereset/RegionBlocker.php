<?php
namespace minereset;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

class RegionBlocker implements Listener{
    /** @var MineReset  */
    private $plugin;
    /** @var array[] $activeZones */
    private $activeZones = [];
    public function __construct(MineReset $mineReset){
        $this->plugin = $mineReset;
        $this->activeZones = [];
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
    }
    /**
     * @priority HIGH
     *
     * @param PlayerMoveEvent $event
     */
    public function onPlayerMove(PlayerMoveEvent $event){
        if(isset($this->activeZones[$event->getPlayer()->getLevel()->getId()])){
            foreach($this->activeZones[$event->getPlayer()->getLevel()->getId()] as $zone){
                if($this->isInsideZone($event->getTo(), $zone[0], $zone[1])){
                    $event->setCancelled();
                    $event->getPlayer()->sendMessage(TextFormat::RED . "You can't go in there, a mine is resetting." . TextFormat::RESET);
                    return;
                }
            }
        }
    }
    /**
     * @param Vector3 $a
     * @param Vector3 $b
     * @param Level $level
     * @return int
     */
    public function blockZone(Vector3 $a, Vector3 $b, Level $level){
        if(!isset($this->activeZones[$level->getId()])) $this->activeZones[$level->getId()] = [];
        $id = count($this->activeZones[$level->getId()]);
        $this->activeZones[$level->getId()][$id] = [$a, $b];
        $this->clearZone($level, $id);
        return $id;
    }
    /**
     * @param int $id
     * @param int $level
     */
    public function freeZone(int $id, int $level){
        if(isset($this->activeZones[$level]) && isset($this->activeZones[$level][$id])){
            unset($this->activeZones[$level][$id]);
        }
    }
    /**
     * @param Vector3 $test
     * @param Vector3 $a
     * @param Vector3 $b
     * @return bool
     */
    protected function isInsideZone(Vector3 $test, Vector3 $a, Vector3 $b){
        return ($test->getX() >= $a->getX() && $test->getX() <= $b->getX() && $test->getY() >= $a->getY() && $test->getY() <= $b->getY() && $test->getZ() >= $a->getZ() && $test->getZ() <= $b->getZ());
    }
    /**
     * @param Level $level
     * @param int $id
     */
    protected function clearZone(Level $level, int $id){
        $level = $level->getId();
        if(isset($this->activeZones[$level]) && isset($this->activeZones[$level][$id])){
            foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
                if($player->getLevel()->getId() === $level && $this->isInsideZone($player->getPosition(), $this->activeZones[$level][$id][0], $this->activeZones[$level][$id][1])){
                    $player->teleport($player->getSpawn());
                    $player->sendMessage("You have been teleported because you were inside a mine while it was resetting.");
                }
            }
        }
    }
}
