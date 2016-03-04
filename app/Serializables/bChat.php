<?php

namespace App\Serializables;

use App\Libraries\PacketHandler;
use App\Libraries\Packets;
use App\Libraries\PhpBinaryReader\BinaryReader;
use App\Libraries\Player;
use Log;
use Redis;
use App\Libraries\Helper;

class bChat
{
    public $message = "";
    public $receiver = "";
    public $other = "";
    public $channel = "";
    private $redis;
    private $stream;

    /**
     * bChat constructor.
     * @param BinaryReader|null $stream
     */
    public function __construct(BinaryReader &$stream = null)
    {
        if($stream != null) $this->stream = $stream;
        $this->redis = Redis::connection();
    }

    public function readMessage()
    {
        $this->other = $this->stream->readULEB128();
        $this->message = $this->stream->readULEB128();
        $this->receiver = $this->stream->readULEB128();
    }

    public function readChannel()
    {
        $this->channel = $this->stream->readULEB128();
    }

    /**
     * @param $playerID
     * @param null $channel
     */
    public function joinChannel($playerID, $channel = null)
    {
        if($channel != null) $this->channel = $channel;
        $redis = Redis::connection();
        $key = sprintf("UserInfo:%d", $playerID);
        $redis->sadd($key, $this->channel);
        log::info($redis->smembers($key));
    }

    /**
     * @param $playerID
     */
    public function leaveChannel($playerID)
    {
        $redis = Redis::connection();
        $key = sprintf("UserInfo:%d", $playerID);
        $redis->srem($key, $this->channel);
        log::info($redis->smembers($key));
    }

    /**
     * @param $playerID
     */
    public function sendMessage($playerID)
    {
        $playerHelper = new Player();
        $helper = new Helper();
        $player = $playerHelper->getDatafromID($playerID);
        $userIDs = $playerHelper->getAllIDs($playerHelper->getAllTokens());
        $timestamp = $helper->getTimestamp(true);
        if(substr($this->receiver, 0, 1) === "#") {
            $pipe = $this->redis->pipeline();
            foreach ($userIDs as $userID) {
                if ($userID != $playerID) {
                    if($this->redis->sismember(sprintf("UserInfo:%d", $userID), $this->receiver)) {
                        $pipe->hmset(sprintf("newChatH:%d:%s:%s", $userID, $this->receiver, $timestamp), [
                            'Timestamp' => $timestamp,
                            'UserName' => $player->name,
                            'Message' => $this->message,
                            'Receiver' => $this->receiver,
                            'UserID' => $player->id
                        ]);
                        $pipe->expire(sprintf("newChatH:%d:%s:%s", $userID, $this->receiver, $timestamp), 10);
                    }
                }
            }
            $pipe->execute();
        } else {
            $user = $playerHelper->getDataFromName($this->receiver);
            $this->redis->hmset(sprintf("newChatH:%d:PM:%s", $user->id, $timestamp), [
                'Timestamp' => $timestamp,
                'UserName' => $player->name,
                'Message' => $this->message,
                'Receiver' => $this->receiver,
                'UserID' => $player->id
            ]);
            $this->redis->expire(sprintf("newChatH:%d:PM:%s", $user->id, $timestamp), 10);
        }
    }

    /**
     * @param $playerID
     */
    public function receiveMessage($playerID)
    {
        $channels = $this->redis->smembers(sprintf("UserInfo:%d", $playerID));
        array_push($channels, 'PM');
        $messageKeys = $this->redis->pipeline(function($pipe) use ($channels, $playerID) {
            foreach($channels as $channel)
            {
                $pipe->keys(sprintf("newChatH:%d:%s:*", $playerID, $channel));
            }
        });
        $messageKeys = collect($messageKeys)->collapse();
        if($messageKeys->count() > 0) {
            $messages = $this->redis->pipeline(function($pipe) use ($messageKeys) {
                foreach ($messageKeys as $messageKey) {
                    $pipe->hgetall($messageKey);
                };
            });
            $messages = collect($messages)->sortBy('Timestamp');
            $packetHandler = new PacketHandler();
            foreach($messages as $message)
            {
                $packetHandler->create(Packets::OUT_SendChatMSG, $message);
            }
            $this->redis->del($messageKeys->toArray());
        }
    }
}