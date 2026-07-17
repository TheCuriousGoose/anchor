@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('imgs/logo.png') }}" class="logo" alt="" width="36" height="36">
<span class="logo-text">{!! $slot !!}</span>
</a>
</td>
</tr>
