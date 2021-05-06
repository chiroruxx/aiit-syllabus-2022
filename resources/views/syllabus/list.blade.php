@extends('layout')
@section('title', 'AIIT 2021 syllabus')

@section('content')
    @php
        /**
         * @var \Illuminate\Support\Collection&\App\Models\Syllabus[] $syllabi
         * @var \App\Enums\Course[] $courses
         * @var \App\Enums\ModelType[] $modelTypes
         * @var int[] $quarters
         * @var int[][] $selected
         */
    @endphp
    <header class="w-full py-16 bg-yellow-300">
        <h1 class="text-3xl ml-4">AIIT 2021 syllabus</h1>
    </header>

    <form method="GET" class="my-16 pl-4">
        <div class="grid gap-y-4" style="grid-template-columns: 5rem 1fr;">
            <span>コース: </span>
            <div>
                @foreach($courses as $course)
                    <label class="inline-block mr-4">
                        <input type="checkbox"
                               name="search[courses][]"
                               value="{{ $course->getValue()}}"
                               @if(in_array($course->getValue(), $selected['courses'], true)) checked="checked" @endif
                               class="flex-none"
                        >
                        {{ $course->label() }}
                    </label>
                @endforeach
            </div>
            <span>時期: </span>
            <div class="flex flex-row">
                @foreach($quarters as $quarter)
                    <label class="inline-block mr-4">
                        <input type="checkbox"
                               name="search[quarters][]"
                               value="{{ $quarter}}"
                               @if(in_array($quarter, $selected['quarters'], true)) checked="checked" @endif
                        >
                        {{ $quarter }}Q
                    </label>
                @endforeach
            </div>
            <span>人物像: </span>
            <div>
                @foreach($modelTypes as $modelType)
                    <label class="inline-block mr-4">
                        <input type="checkbox"
                               name="search[model][types][]"
                               value="{{ $modelType->getValue()}}"
                               @if(in_array($modelType->getValue(), $selected['model']['types'], true)) checked="checked" @endif
                        >
                        {{ $modelType->label() }}
                    </label>
                @endforeach
            </div>
        </div>
        <button type="submit" class="border rounded-md mt-8 px-4 py-2 bg-transparent">
            @include('icons.search')
        </button>
    </form>

    <table class="my-16 w-full border-collapse">
        <thead>
        <tr class="border-double border-b-2 border-gray-400 bg-gray-100">
            <th class="py-2 pl-4 text-left">授業名</th>
            <th class="py-2 text-left">コース</th>
            <th class="py-2">時期</th>
        </tr>
        </thead>
        <tbody>
        @foreach($syllabi as $syllabus)
            <tr class="even:bg-gray-100 hover:bg-gray-200">
                <td class="py-2 pl-4"><a href="{{ route('syllabus.show', $syllabus) }}" class="underline">{{ $syllabus->name_ja }}</a></td>
                <td class="py-2">{{ $syllabus->getCourseLabel() }}</td>
                <td class="py-2 text-center">{{ $syllabus->quarter }}Q</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
