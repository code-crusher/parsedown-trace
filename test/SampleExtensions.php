<?php

class UnsafeExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['element']['text'];
        unset($Block['element']['element']['text']);

        // WARNING: There is almost always a better way of doing things!
        //
        // This example is one of them, unsafe behaviour is NOT needed here.
        // Only use this if you trust the input and have no idea what
        // the output HTML will look like (e.g. using an external parser).
        $Block['element']['element']['rawHtml'] = "<p>$text</p>";

        return $Block;
    }
}


class TrustDelegatedExtension extends Parsedown
{
    protected function blockFencedCodeComplete($Block)
    {
        $text = $Block['element']['element']['text'];
        unset($Block['element']['element']['text']);

        // WARNING: There is almost always a better way of doing things!
        //
        // This behaviour is NOT needed in the demonstrated case.
        // Only use this if you are sure that the result being added into
        // rawHtml is safe.
        // (e.g. using an external parser with escaping capabilities).
        $Block['element']['element']['rawHtml'] = "<p>$text</p>";
        $Block['element']['element']['allowRawHtmlInSafeMode'] = true;

        return $Block;
    }
}


class UnmarkedTextExtension extends Parsedown
{
    // Convert bare email addresses in unmarked text into links, the way
    // MantisBT does. See https://github.com/erusev/parsedown/issues/898
    protected function unmarkedText($text)
    {
        $text = preg_replace(
            '/([\w.+-]+@[\w-]+(?:\.[\w-]+)+)/',
            '<a href="mailto:$1">$1</a>',
            $text
        );

        return parent::unmarkedText($text);
    }
}


class InheritedUnmarkedTextExtension extends UnmarkedTextExtension
{
}


class AbbreviationExtension extends Parsedown
{
    private $abbreviations = array();

    function addAbbreviation($abbreviation, $meaning)
    {
        $this->abbreviations[$abbreviation] = $meaning;

        return $this;
    }

    // Inserts <abbr> elements into unmarked text, the way ParsedownExtra
    // 0.7 does.
    protected function unmarkedText($text)
    {
        $text = parent::unmarkedText($text);

        foreach ($this->abbreviations as $abbreviation => $meaning)
        {
            $pattern = '/\b' . preg_quote($abbreviation, '/') . '\b/';

            $text = preg_replace(
                $pattern,
                '<abbr title="' . $meaning . '">' . $abbreviation . '</abbr>',
                $text
            );
        }

        return $text;
    }
}
