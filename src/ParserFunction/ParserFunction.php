<?php

namespace MWUnit\ParserFunction;

use MWUnit\ParserData;

interface ParserFunction {
	/**
	 * Executes the parser function.
	 *
	 * @param ParserData $data
	 * @return string|array
	 */
	public function execute( ParserData $data );
}
