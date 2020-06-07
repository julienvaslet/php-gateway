<?php
// Copyright (c) 2020 Julien Vaslet

require_once(__DIR__."/Route.class.php");
require_once(__DIR__."/Router.class.php");
require_once(__DIR__."/SerializableObject.class.php");
require_once(__DIR__."/ApiDocumentationRoute.class.php");
require_once(__DIR__."/exceptions/Exception.class.php");
require_once(__DIR__."/exceptions/InternalServerError.class.php");
require_once(__DIR__."/exceptions/InvalidParameterException.class.php");
require_once(__DIR__."/exceptions/InvalidParameterTypeException.class.php");
require_once(__DIR__."/exceptions/MethodNotAllowedException.class.php");
require_once(__DIR__."/exceptions/MissingParameterException.class.php");
require_once(__DIR__."/exceptions/NotFoundException.class.php");
require_once(__DIR__."/responses/Response.class.php");
require_once(__DIR__."/responses/JsonResponse.class.php");
require_once(__DIR__."/responses/HtmlResponse.class.php");
