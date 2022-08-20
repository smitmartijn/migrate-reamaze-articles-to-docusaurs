<?php

/**
 * This script fetches all articles from Reamaze and saves them as markdown files,
 * organised in folders of topic names that are used in Reamaze. It adds the URL of the
 * article at Reamaze, so you can format a redirection schema.
 *
 * I've used this script for a migration from Reamaze to Docusaurus(.io), but it can be
 * used for any purpose where you can use markdown.
 *
 * Fill out the 3 configuration options below and run it.
 *
 * 2022 Martijn Smit <@smitmartijn>
 */

// the reamaze.io URL to your portal
$reamaze_url = "https://yourbrand.reamaze.io";
// your Reamaze username
$reamaze_username = "your@email.tld";
// find your token at Settings -> Developer menu -> API Token
$reamaze_api_token = "asdf1234";

function apiCall($uri)
{
  global $reamaze_api_token, $reamaze_username, $reamaze_url;
  $url = $reamaze_url . "/" . $uri;
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
  curl_setopt($ch, CURLOPT_USERPWD, $reamaze_username . ":" . $reamaze_api_token);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $return = curl_exec($ch);
  curl_close($ch);
  return $return;
}

function reamazeGetArticles()
{
  $articles = [];
  // get the first page and store the first articles
  echo "Retrieving first page of Reamaze articles..\n";
  $result = apiCall("/api/v1/articles");
  $json = json_decode($result, true);
  $articles += $json['articles'];
  $page_count = $json['page_count'];
  for ($i = 2; $i <= $page_count; $i++) {
    echo "Retrieving page " . $i . " of Reamaze articles..\n";
    $result = apiCall("/api/v1/articles?page=" . $i);
    $json = json_decode($result, true);
    $articles = array_merge($articles, $json['articles']);
  }
  return $articles;
}

$articles = reamazeGetArticles();

foreach ($articles as $article) {
  $title = $article['title'];
  $body = $article['body'];
  $slug = $article['slug'];
  $url = $article['url'];
  $topic_slug = $article['topic']['slug'];

  // add a header to all files and store the old URL, so we can redirect
  $md_body = "---\n";
  $md_body .= "old_reamaze_url: \"" . $url . "\"\n";
  $md_body .= "---\n";
  $md_body .= "# " . $title . "\n";
  $md_body .= "\n";
  $md_body .= $body;

  // create a directory for the topic, if it doesn't exist
  if (!file_exists($topic_slug)) {
    mkdir($topic_slug, 0755, true);
  }
  // create markdown file and write the body to it
  $md_file_name = $topic_slug . "/" . $slug . ".md";
  echo "Writing file: " . $md_file_name . "\n";
  $md_file = fopen($md_file_name, "w") or die("Unable to open file!");

  fwrite($md_file, $md_body);
  fclose($md_file);
}
