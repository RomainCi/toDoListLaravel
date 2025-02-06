@props(['url'])
<tr>
<td class="header">
<a href="{{env('FRONTEND_URL') }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://www.rc-dev.pro/assets/logo/rctech.webp" class="logo" alt="Laravel Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
