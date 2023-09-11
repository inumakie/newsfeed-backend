<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FeedController extends Controller
{

    public function index(Request $request)
    { 
        //API KEYS
        $newsApiKey = env('NEWSAPI_API_KEY');
        $nytApiKey = env('NYT_API_KEY');
        $theGuardianApiKey = env('THE_GUARDIAN_API_KEY');

        //pagination
        $page = $request->input('page', 1);
        $perPage = $request->input('perPage', 10); //to modify # of results in the newsAPI

        //sources
        $sources = $request->input('sources');
        $selectedSources = explode(',', $sources);
        
        // Fetching

        $mergedData = [];

        if (in_array('newsAPI', $selectedSources)) {
            $response = Http::withHeaders([
                'x-api-key' => $newsApiKey,
            ])  
            ->get('https://newsapi.org/v2/top-headlines', [
                'q' => $request->input('search') ?? 'news',
                'page' => $request->input('newSearch') === 'true' ? 1 : $page,
                'pageSize' => $perPage,
                'sources' => '',
            ]);
    
            $normalizedData = $this->normalizeDataFromApi1($response);
            $mergedData = array_merge($mergedData, $normalizedData);
        }
    
        if (in_array('nyt', $selectedSources)) {
            $response2 = Http::get('https://api.nytimes.com/svc/search/v2/articlesearch.json', [
                'api-key' => $nytApiKey,
                'q' => $request->input('search'),
                'page' => $request->input('newSearch') === 'true' ? 1 : $page,
                'begin_date' => !empty($request->input('fromDate')) ? $request->input('fromDate') : null,
                'end_date' => !empty($request->input('toDate')) ? $request->input('toDate') : null,
            ]);
    
            $normalizedData2 = $this->normalizeDataFromApi2($response2);
            $mergedData = array_merge($mergedData, $normalizedData2);
        }
    
        if (in_array('guardian', $selectedSources)) {
            $response3 = Http::get('https://content.guardianapis.com/search', [
                'api-key' => $theGuardianApiKey,
                'q' => $request->input('search'),
                'show-fields' => 'thumbnail',
                'page' => $request->input('newSearch') === 'true' ? 1 : $page,
                'from-date' => !empty($request->input('fromDate')) ? $request->input('fromDate') : null,
                'to-date' => !empty($request->input('toDate')) ? $request->input('toDate') : null,
            ]);
    
            $normalizedData3 = $this->normalizeDataFromApi3($response3);
            $mergedData = array_merge($mergedData, $normalizedData3);
        }

        $uniqueData = collect($mergedData)->unique('id')->values()->all();

        return response()->json([
            'articles' => $uniqueData, //
            'total' => count($uniqueData),
            'currentPage' => $page,
        ]);

    }


    private function normalizeDataFromApi1($response)
    {

        $normalizedData = [];

        if ($response->successful()) {
            $data = $response->json();

            foreach ($data['articles'] as $doc) {

                $image = isset($doc['urlToImage']) ? $doc['urlToImage'] : '';

                $normalizedArticle = [
                    'id' => $doc['url'],
                    'title' => $doc['title'],
                    'content' => $doc['content'],
                    'image' => $image,
                    'date' => $doc['publishedAt'],
                    'author' => $doc['author'],
                    'source' => $doc['source']['name'],
                ];

                $normalizedData[] = $normalizedArticle;
            }
        }

        return $normalizedData;
    }

    private function normalizeDataFromApi2($response2)
    {
        $normalizedData2 = [];

        if ($response2->successful()) {
            $data = $response2->json();

            foreach ($data['response']['docs'] as $doc) {

                $image = isset($doc['multimedia'][0]['url']) ? 'https://static01.nyt.com/'. $doc['multimedia'][0]['url'] : '';

                $normalizedDoc = [
                    'id' => $doc['web_url'],
                    'title' => $doc['headline']['main'],
                    'content' => $doc['lead_paragraph'],
                    'image' => $image,
                    'date' => $doc['pub_date'],
                    'author' => $doc['byline']['original'],
                    'source' => "The New York Times",
                ];

                $normalizedData2[] = $normalizedDoc;
            }
        }

        return $normalizedData2;
    }

    private function normalizeDataFromApi3($response3)
    {
        $normalizedData3 = [];

        if ($response3->successful()) {
            $data = $response3->json();

            foreach ($data['response']['results'] as $doc) {

                $image = isset($doc['fields']['thumbnail']) ? $doc['fields']['thumbnail'] : '';

                $normalizedDoc = [
                    'id' => $doc['webUrl'],
                    'title' => $doc['webTitle'],
                    'content' => $doc['webTitle'],
                    'image' => $image,
                    'date' => $doc['webPublicationDate'],
                    'author' => 'The Guardian',
                    'source' => 'The Guardian',
                ];

                $normalizedData3[] = $normalizedDoc;
            }
        }

        return $normalizedData3;
    }
}
