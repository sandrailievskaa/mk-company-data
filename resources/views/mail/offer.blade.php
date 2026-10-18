<!doctype html>
<html lang="mk">
<body style="font-family: Arial, Helvetica, sans-serif; font-size: 15px; line-height: 1.6; color: #1f2937;">
    @foreach ($paragraphs as $paragraph)
        <p>{!! nl2br(e($paragraph)) !!}</p>
    @endforeach

    {{-- Open tracking pixel; opens are indicative only (some clients block or prefetch images). --}}
    <img src="{{ $trackingUrl }}" width="1" height="1" alt="" style="display:none">
</body>
</html>
