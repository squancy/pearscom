<?php
  function chooseCover($cat){
    $cover = "";
    if($cat == "School"){
      $cover = '<img src="/images/art_cover/school.jpg"   class="cover_art">';
    }else if($cat == "Business"){
      $cover = '<img src="/images/art_cover/business.jpg"   class="cover_art">';
    }else if($cat == "Learning"){
      $cover = '<img src="/images/art_cover/learning.jpg"   class="cover_art">';
    }else if($cat == "My Dreams"){
      $cover = '<img src="/images/art_cover/dreams.jpg"   class="cover_art">';
    }else if($cat == "Money"){
      $cover = '<img src="/images/art_cover/money.jpg"   class="cover_art">';
    }else if($cat == "Technology"){
      $cover = '<img src="/images/art_cover/technology.jpg"   class="cover_art">';
    }else if($cat == "Video Games"){
      $cover = '<img src="/images/art_cover/vgames.jpg"   class="cover_art">';
    }else if($cat == "TV programmes"){
      $cover = '<img src="/images/art_cover/tv.jpg"   class="cover_art">';
    }else if($cat == "Hobbies"){
      $cover = '<img src="/images/art_cover/hobbies.jpg"   class="cover_art">';
    }else if($cat == "Music"){
      $cover = '<img src="/images/art_cover/music.jpg"   class="cover_art">';
    }else if($cat == "Freetime"){
      $cover = '<img src="/images/art_cover/freetime.jpg"   class="cover_art">';
    }else if($cat == "Travelling"){
      $cover = '<img src="/images/art_cover/travelling.jpg"   class="cover_art">';
    }else if($cat == "Books"){
      $cover = '<img src="/images/art_cover/books.jpg"   class="cover_art">';
    }else if($cat == "Politics"){
      $cover = '<img src="/images/art_cover/politics.jpg"   class="cover_art">';
    }else if($cat == "Movies"){
      $cover = '<img src="/images/art_cover/movies.jpg"   class="cover_art">';
    }else if($cat == "Lifestyle"){
      $cover = '<img src="/images/art_cover/lifestyle.jpg"   class="cover_art">';
    }else if($cat == "Food"){
      $cover = '<img src="/images/art_cover/food.jpg"   class="cover_art">';
    }else if($cat == "Knowledge"){
      $cover = '<img src="/images/art_cover/knowledge.jpg"   class="cover_art">';
    }else if($cat == "Language"){
      $cover = '<img src="/images/art_cover/language.jpg"   class="cover_art">';
    }else if($cat == "Experiences"){
      $cover = '<img src="/images/art_cover/experiences.jpg"   class="cover_art">';
    }else if($cat == "Love"){
      $cover = '<img src="/images/art_cover/love.jpg"   class="cover_art">';
    }else if($cat == "Recipes"){
      $cover = '<img src="/images/art_cover/recipes.jpg"   class="cover_art">';
    }else if($cat == "Personal Stories"){
      $cover = '<img src="/images/art_cover/pstories.jpg"   class="cover_art">';
    }else if($cat == "Product Review"){
      $cover = '<img src="/images/art_cover/preview.jpg"   class="cover_art">';
    }else if($cat == "History"){
      $cover = '<img src="/images/art_cover/history.jpg"   class="cover_art">';
    }else if($cat == "Religion"){
      $cover = '<img src="/images/art_cover/religion.jpg"   class="cover_art">';
    }else if($cat == "Entertaintment"){
      $cover = '<img src="/images/art_cover/ent.jpg"   class="cover_art">';
    }else if($cat == "News"){
      $cover = '<img src="/images/art_cover/news.jpg"   class="cover_art">';
    }else if($cat == "Animals"){
      $cover = '<img src="/images/art_cover/animals.jpg"   class="cover_art">';
    }else if($cat == "Environment"){
      $cover = '<img src="/images/art_cover/env.jpg"   class="cover_art">';
    }else if($cat == "Issues"){
      $cover = '<img src="/images/art_cover/issues.jpg"   class="cover_art">';
    }else if($cat == "The Future"){
      $cover = '<img src="/images/art_cover/future.jpg"   class="cover_art">';
    }else{
      $cover = '<img src="/images/art_cover/sports.jpg"   class="cover_art">';
    }
    return $cover;
  }
?>
