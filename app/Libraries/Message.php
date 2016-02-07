<?php

namespace App\Libraries;

use Cache;

class Message
{
    public function sendToChannel($data, $user)
    {
        $packet = new Packet();
        $player = new Player();
        $messageArray = array();
        foreach (array_slice($data, 11) as $item) {
            if ($item == 11) {
                break;
            }
            array_push($messageArray, $item);
        }
        $message = implode(array_map("chr", $messageArray));
        $toChannelArray = array();
        foreach (array_slice($data, 11 + count($messageArray) + 2) as $item) {
            if ($item == 0) {
                break;
            }
            array_push($toChannelArray, $item);
        }
        $channel = implode(array_map("chr", $toChannelArray));
        if($this->isCommand($messageArray))
        {
            return $this->command($messageArray, $user);
        }
        $ids = $player->getAllIDs($player->getAllTokens());
        foreach($ids as $id)
        {
            if($id != $user->id) {
                $previousMessage = $this->getPreviousMessages($id);
                Cache::tags(['userChat'])->put($id, array_merge($previousMessage,
                    $packet->create(07, array($user->name, $message, $channel, $user->id))
                ), 1);
            }
        }
        return array();
    }

    public function sendToPlayer($data, $user)
    {
        $packet = new Packet();
        $player = new Player();
        $messageArray = array();
        foreach (array_slice($data, 11) as $item) {
            if ($item == 11) {
                break;
            }
            array_push($messageArray, $item);
        }
        $message = implode(array_map("chr", $messageArray));
        $toPersonArray = array();
        foreach (array_slice($data, 11 + count($messageArray) + 2) as $item) {
            if ($item == 0) {
                break;
            }
            array_push($toPersonArray, $item);
        }
        if($this->isCommand($messageArray))
        {
            return $this->command($messageArray, $user);
        }
        $toPerson = $player->isPlayerOnline(implode(array_map("chr", $toPersonArray)));
        if(isset($toPerson))
        {
            $previousMessage = $this->getPreviousMessages($toPerson->id);
            Cache::tags(['userChat'])->put($toPerson->id, array_merge($previousMessage,
                $packet->create(07, array($user->name, $message, $toPerson->name, $user->id))
            ), 1);
            return array();
        }
        return $packet->create(24, sprintf("%s is not online", implode(array_map("chr", $toPersonArray))));
    }

    public function getPreviousMessages($id)
    {
        if(Cache::tags(['userChat'])->has($id))
        {
            return Cache::tags(['userChat'])->get($id);
        }
        return array();
    }

    public function isCommand($messageArray)
    {
        if($messageArray[0] == 33)
        {
            return true;
        } else {
            return false;
        }
    }

    public function command($messageArray, $user)
    {
        $packet = new Packet();
        $command = array();
        foreach($messageArray as $item)
        {
            if($item != 32)
            {
                array_push($command, $item);
            }
        }

        switch(implode(array_map("chr", $command)))
        {
            case "!roll":
                $previousMessage = $this->getPreviousMessages($user->id);
                Cache::tags(['userChat'])->put($user->id, array_merge($previousMessage,
                    $packet->create(07, array("KaiBanchoo", sprintf("You rolled a %d", rand(1,100)), $user->name, 2))
                ), 1);
                break;
            default:
                $previousMessage = $this->getPreviousMessages($user->id);
                Cache::tags(['userChat'])->put($user->id, array_merge($previousMessage,
                    $packet->create(07, array("KaiBanchoo", sprintf("The command %s doesn't exist", implode(array_map("chr", $command))), $user->name, 2))
                ), 1);
                break;
        }
        return array();
    }
}