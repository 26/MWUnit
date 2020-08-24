<?php

namespace MWUnit\ParserTag;

use MWUnit\ParserData;

interface ParserTag {
    /**
     * Executes the parser tag.
     *
     * @param ParserData $data
     * @return string|array
     */
    public function execute( ParserData $data );
}