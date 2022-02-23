<?php

namespace Kiri\Annotation\Route;

enum Method
{


	case REQUEST_POST;
	case REQUEST_GET;
	case REQUEST_HEAD;
	case REQUEST_OPTIONS;
	case REQUEST_DELETE;
	case REQUEST_PUT;

}
