<!doctype html>
<html class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="dist/css/style.css?v={{time()}}" rel="stylesheet">
    <!-- Font -->
    <link href="dist/css/line-awesome.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.css" />
    <!-- Font -->
</head>

<body class="block bg-gradient-to-b dark:from-slate-800 dark:to-stone-800 from-gray-200 to-gray-100 bg-opacity-30 dark:bg-opacity-100 min-h-screen">
    <div id="mobile" class="demo2 relative min-h-screen">
      <div id="mobileBodyContent">
            @include('frontend.includes.header')
              <section class="relative py-24 overflow-hidden md:w-1/3 mx-auto">
                <div class="container px-4 mx-auto relative">
                    <h3 class="text-4xl lg:text-5xl text-center font-heading mb-12 text-[#AF0F40]">Error 405</h3>
                  <div class="max-w-md mx-auto text-center text-white relative z-10">
                    <p class="mb-10">Sorry, we can&apos;t find that page or something has gone wrong...</p>
                    <a class="inline-block px-8 py-3 text-white font-bold bg-black hover:bg-gray-900" href="{{route('fdashboard')}}">Go back to Homapage</a>
                  </div>
                </div>
              </section>
      </div>
    </div>
@include('frontend.includes.footer')
</body>

</html>