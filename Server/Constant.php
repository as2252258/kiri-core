<?php


namespace Server;


/**
 * Class Constant
 * @package Server
 */
class Constant
{

    const START = 'Start';
    const SHUTDOWN = 'Shutdown';
    const WORKER_START = 'WorkerStart';
    const WORKER_STOP = 'WorkerStop';
    const WORKER_EXIT = 'WorkerExit';
    const CONNECT = 'Connect';
    const HANDSHAKE = 'handshake';
    const DISCONNECT = 'disconnect';
    const MESSAGE = 'message';
    const RECEIVE = 'Receive';
    const PACKET = 'Packet';
    const REQUEST = 'request';
    const CLOSE = 'Close';
    const TASK = 'Task';
    const FINISH = 'Finish';
    const PIPE_MESSAGE = 'PipeMessage';
    const WORKER_ERROR = 'WorkerError';
    const MANAGER_START = 'ManagerStart';
    const MANAGER_STOP = 'ManagerStop';
    const BEFORE_RELOAD = 'BeforeReload';
    const AFTER_RELOAD = 'AfterReload';





    const SERVER_TYPE_HTTP = 'http';
    const SERVER_TYPE_WEBSOCKET = 'ws';
    const SERVER_TYPE_TCP = 'tcp';
    const SERVER_TYPE_UDP = 'udp';
    const SERVER_TYPE_BASE = 'base';

}
