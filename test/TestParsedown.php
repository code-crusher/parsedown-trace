<?php

class TestParsedown extends Parsedown
{
    public function getTextLevelElements()
    {
        return $this->textLevelElements;
    }

    public function extractElement(array $Component)
    {
        return parent::extractElement($Component);
    }
}
