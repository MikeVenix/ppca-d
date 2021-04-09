<p>
Account; {{ $name }}<br/>
Ad Group; {{ $id }}<br/>
The following keywords were found without a + before them;<br/>
@php
foreach($words as $word) {
    echo($word);
    echo("<br/>");
};
@endphp
</p>
