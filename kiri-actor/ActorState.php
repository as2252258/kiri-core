<?php

namespace Kiri\Actor;

enum ActorState
{
	case IDLE;
	case BUSY;

	/**
	 *
	 */
	case CREATE;
	case MESSAGE;
	case SHUTDOWN;

}

