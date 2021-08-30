<?php

namespace Server\Message;

class StatusCode
{

	const CODE_100 = 100;
	const CODE_101 = 101;
	const CODE_200 = 200;
	const CODE_201 = 201;
	const CODE_202 = 202;
	const CODE_203 = 203;
	const CODE_204 = 204;
	const CODE_205 = 205;
	const CODE_206 = 206;
	const CODE_300 = 300;
	const CODE_301 = 301;
	const CODE_302 = 302;
	const CODE_303 = 303;
	const CODE_304 = 304;
	const CODE_305 = 305;
	const CODE_307 = 307;
	const CODE_400 = 400;
	const CODE_401 = 401;
	const CODE_403 = 403;
	const CODE_404 = 404;
	const CODE_405 = 405;
	const CODE_406 = 406;
	const CODE_407 = 407;
	const CODE_408 = 408;
	const CODE_409 = 409;
	const CODE_410 = 410;
	const CODE_411 = 411;
	const CODE_412 = 412;
	const CODE_413 = 413;
	const CODE_414 = 414;
	const CODE_415 = 415;
	const CODE_416 = 416;
	const CODE_417 = 417;
	const CODE_423 = 423;
	const CODE_500 = 500;
	const CODE_501 = 501;
	const CODE_502 = 502;
	const CODE_503 = 503;
	const CODE_504 = 504;
	const CODE_505 = 505;


	const CODE_STATUS = [
		self::CODE_100 => 'Continue 初始的请求已经接受，客户应当继续发送请求的其余部分。（HTTP 1.1新）',
		self::CODE_101 => 'Switching Protocols 服务器将遵从客户的请求转换到另外一种协议（HTTP 1.1新）',
		self::CODE_200 => '（成功） 服务器已成功处理了请求。 通常，这表示服务器提供了请求的网页。',
		self::CODE_201 => '（已创建） 请求成功并且服务器创建了新的资源。',
		self::CODE_202 => '（已接受） 服务器已接受请求，但尚未处理。',
		self::CODE_203 => '（非授权信息） 服务器已成功处理了请求，但返回的信息可能来自另一来源。',
		self::CODE_204 => '（无内容） 服务器成功处理了请求，但没有返回任何内容。',
		self::CODE_205 => '（重置内容） 服务器成功处理了请求，但没有返回任何内容。',
		self::CODE_206 => '（部分内容） 服务器成功处理了部分 GET 请求。',
		self::CODE_300 => '（多种选择） 针对请求，服务器可执行多种操作。 服务器可根据请求者 (user agent) 选择一项操作，或提供操作列表供请求者选择。',
		self::CODE_301 => '（永久移动） 请求的网页已永久移动到新位置。 服务器返回此响应（对 GET 或 HEAD 请求的响应）时，会自动将请求者转到新位置。',
		self::CODE_302 => '（临时移动） 服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求。',
		self::CODE_303 => '（查看其他位置） 请求者应当对不同的位置使用单独的 GET 请求来检索响应时，服务器返回此代码。',
		self::CODE_304 => '（未修改） 自从上次请求后，请求的网页未修改过。 服务器返回此响应时，不会返回网页内容。',
		self::CODE_305 => '（使用代理） 请求者只能使用代理访问请求的网页。 如果服务器返回此响应，还表示请求者应使用代理。',
		self::CODE_307 => '（临时重定向） 服务器目前从不同位置的网页响应请求，但请求者应继续使用原有位置来进行以后的请求。',
		self::CODE_400 => '（错误请求） 服务器不理解请求的语法。',
		self::CODE_401 => '（未授权） 请求要求身份验证。 对于需要登录的网页，服务器可能返回此响应。',
		self::CODE_403 => '（禁止） 服务器拒绝请求。',
		self::CODE_404 => '（未找到） 服务器找不到请求的网页。',
		self::CODE_405 => '（方法禁用） 禁用请求中指定的方法。',
		self::CODE_406 => '（不接受） 无法使用请求的内容特性响应请求的网页。',
		self::CODE_407 => '（需要代理授权） 此状态代码与 401（未授权）类似，但指定请求者应当授权使用代理。',
		self::CODE_408 => '（请求超时） 服务器等候请求时发生超时。',
		self::CODE_409 => '（冲突） 服务器在完成请求时发生冲突。 服务器必须在响应中包含有关冲突的信息。',
		self::CODE_410 => '（已删除） 如果请求的资源已永久删除，服务器就会返回此响应。',
		self::CODE_411 => '（需要有效长度） 服务器不接受不含有效内容长度标头字段的请求。',
		self::CODE_412 => '（未满足前提条件） 服务器未满足请求者在请求中设置的其中一个前提条件。',
		self::CODE_413 => '（请求实体过大） 服务器无法处理请求，因为请求实体过大，超出服务器的处理能力。',
		self::CODE_414 => '（请求的 URI 过长） 请求的 URI（通常为网址）过长，服务器无法处理。',
		self::CODE_415 => '（不支持的媒体类型） 请求的格式不受请求页面的支持。',
		self::CODE_416 => '（请求范围不符合要求） 如果页面无法提供请求的范围，则服务器会返回此状态代码。',
		self::CODE_417 => '（未满足期望值） 服务器未满足"期望"请求标头字段的要求。',
		self::CODE_423 => ' 锁定的错误。',
		self::CODE_500 => '（服务器内部错误） 服务器遇到错误，无法完成请求。',
		self::CODE_501 => '（尚未实施） 服务器不具备完成请求的功能。 例如，服务器无法识别请求方法时可能会返回此代码。',
		self::CODE_502 => '（错误网关） 服务器作为网关或代理，从上游服务器收到无效响应。',
		self::CODE_503 => '（服务不可用） 服务器目前无法使用（由于超载或停机维护）。 通常，这只是暂时状态。',
		self::CODE_504 => '（网关超时） 服务器作为网关或代理，但是没有及时从上游服务器收到请求。',
		self::CODE_505 => '（HTTP 版本不受支持） 服务器不支持请求中所用的 HTTP 协议版本。',
	];

}
