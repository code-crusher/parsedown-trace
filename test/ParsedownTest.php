<?php
require 'SampleExtensions.php';

use PHPUnit\Framework\TestCase;

class ParsedownTest extends TestCase
{
    final function __construct($name = null, array $data = array(), $dataName = '')
    {
        $this->dirs = $this->initDirs();
        $this->Parsedown = $this->initParsedown();

        parent::__construct($name, $data, $dataName);
    }

    private $dirs;
    protected $Parsedown;

    /**
     * @return array
     */
    protected function initDirs()
    {
        $dirs []= dirname(__FILE__).'/data/';

        return $dirs;
    }

    /**
     * @return Parsedown
     */
    protected function initParsedown()
    {
        $Parsedown = new TestParsedown();

        return $Parsedown;
    }

    /**
     * @dataProvider data
     * @param $test
     * @param $dir
     */
    function test_($test, $dir)
    {
        $markdown = file_get_contents($dir . $test . '.md');

        $expectedMarkup = file_get_contents($dir . $test . '.html');

        $expectedMarkup = str_replace("\r\n", "\n", $expectedMarkup);
        $expectedMarkup = str_replace("\r", "\n", $expectedMarkup);

        $this->Parsedown->setSafeMode(substr($test, 0, 3) === 'xss');
        $this->Parsedown->setStrictMode(substr($test, 0, 6) === 'strict');

        $actualMarkup = $this->Parsedown->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);
    }

    function testRawHtml()
    {
        $markdown = "```php\nfoobar\n```";
        $expectedMarkup = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = '<pre><code class="language-php">&lt;p&gt;foobar&lt;/p&gt;</code></pre>';

        $unsafeExtension = new UnsafeExtension;
        $actualMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    function testTrustDelegatedRawHtml()
    {
        $markdown = "```php\nfoobar\n```";
        $expectedMarkup = '<pre><code class="language-php"><p>foobar</p></code></pre>';
        $expectedSafeMarkup = $expectedMarkup;

        $unsafeExtension = new TrustDelegatedExtension;
        $actualMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedMarkup, $actualMarkup);

        $unsafeExtension->setSafeMode(true);
        $actualSafeMarkup = $unsafeExtension->text($markdown);

        $this->assertEquals($expectedSafeMarkup, $actualSafeMarkup);
    }

    function data()
    {
        $data = array();

        foreach ($this->dirs as $dir)
        {
            $Folder = new DirectoryIterator($dir);

            foreach ($Folder as $File)
            {
                /** @var $File DirectoryIterator */

                if ( ! $File->isFile())
                {
                    continue;
                }

                $filename = $File->getFilename();

                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if ($extension !== 'md')
                {
                    continue;
                }

                $basename = $File->getBasename('.md');

                if (file_exists($dir . $basename . '.html'))
                {
                    $data []= array($basename, $dir);
                }
            }
        }

        return $data;
    }

    public function test_no_markup()
    {
        $markdownWithHtml = <<<MARKDOWN_WITH_MARKUP
<div>_content_</div>

sparse:

<div>
<div class="inner">
_content_
</div>
</div>

paragraph

<style type="text/css">
    p {
        color: red;
    }
</style>

comment

<!-- html comment -->
MARKDOWN_WITH_MARKUP;

        $expectedHtml = <<<EXPECTED_HTML
<p>&lt;div&gt;<em>content</em>&lt;/div&gt;</p>
<p>sparse:</p>
<p>&lt;div&gt;
&lt;div class="inner"&gt;
<em>content</em>
&lt;/div&gt;
&lt;/div&gt;</p>
<p>paragraph</p>
<p>&lt;style type="text/css"&gt;
p {
color: red;
}
&lt;/style&gt;</p>
<p>comment</p>
<p>&lt;!-- html comment --&gt;</p>
EXPECTED_HTML;

        $parsedownWithNoMarkup = new TestParsedown();
        $parsedownWithNoMarkup->setMarkupEscaped(true);
        $this->assertEquals($expectedHtml, $parsedownWithNoMarkup->text($markdownWithHtml));
    }

    public function testLateStaticBinding()
    {
        $parsedown = Parsedown::instance();
        $this->assertInstanceOf('Parsedown', $parsedown);

        // After instance is already called on Parsedown
        // subsequent calls with the same arguments return the same instance
        $sameParsedown = TestParsedown::instance();
        $this->assertInstanceOf('Parsedown', $sameParsedown);
        $this->assertSame($parsedown, $sameParsedown);

        $testParsedown = TestParsedown::instance('test late static binding');
        $this->assertInstanceOf('TestParsedown', $testParsedown);

        $sameInstanceAgain = TestParsedown::instance('test late static binding');
        $this->assertSame($testParsedown, $sameInstanceAgain);
    }

    public function testAllowedTagsDropsParagraphTags()
    {
        // https://github.com/erusev/parsedown/issues/894
        // paragraphs are kept apart by line breaks, without the <p> tags

        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array('br', 'strong', 'em', 'code', 'pre', 'a'));

        $markdown = "Hello **world**!\n\nSecond _paragraph_.";
        $expectedMarkup = "Hello <strong>world</strong>!\nSecond <em>paragraph</em>.";

        $this->assertEquals($expectedMarkup, $parsedown->text($markdown));
    }

    public function testAllowedTagsDropsTagsGeneratedByHandlers()
    {
        // the paragraph inside the quote is generated while the markup is
        // being rendered - it has to be dropped as well

        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array('em'));

        $markdown = "> quote _emphasis_";
        $expectedMarkup = "quote <em>emphasis</em>";

        $this->assertEquals($expectedMarkup, $parsedown->text($markdown));
    }

    public function testAllowedTagsKeepsAllowedTags()
    {
        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array('p', 'strong', 'em'));

        $markdown = "Hello **world** and _moon_!";
        $expectedMarkup = '<p>Hello <strong>world</strong> and <em>moon</em>!</p>';

        $this->assertEquals($expectedMarkup, $parsedown->text($markdown));
    }

    public function testAllowedTagsIsCaseInsensitive()
    {
        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array('P'));

        $this->assertEquals('<p>Hello!</p>', $parsedown->text('Hello!'));
    }

    public function testAllowedTagsCanBeDisabled()
    {
        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array());
        $parsedown->setAllowedTags(null);

        $this->assertEquals('<p>Hello!</p>', $parsedown->text('Hello!'));
    }

    public function testAllowedTagsDoesNotAffectOtherInstances()
    {
        $parsedown = new TestParsedown;
        $parsedown->setAllowedTags(array());

        $this->assertEquals('<p>Hello!</p>', (new TestParsedown)->text('Hello!'));
    }

    public function testAllowedTagsDoesNotBypassSafeMode()
    {
        $parsedown = new TestParsedown;
        $parsedown->setSafeMode(true);
        $parsedown->setAllowedTags(array('p', 'a'));

        $markdown = '[link](javascript:alert(1))';
        $expectedMarkup = '<p><a href="javascript%3Aalert(1)">link</a></p>';

        $this->assertEquals($expectedMarkup, $parsedown->text($markdown));
    }
}
