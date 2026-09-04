<?php
namespace App\Http\Controllers\Admin;use App\Http\Controllers\Admin\Concerns\HandlesMediaUploads;use App\Http\Controllers\Controller;use App\Models\{Destination,GalleryAlbum,GalleryCategory,GalleryMedia,Tour,VisaService};use Illuminate\Http\{Request,RedirectResponse};use Illuminate\View\View;
class DomainContentController extends Controller{
 use HandlesMediaUploads;
 private const MAP=['visa-services'=>VisaService::class,'gallery-categories'=>GalleryCategory::class,'gallery-albums'=>GalleryAlbum::class];
 public function index(Request $r,string $resource):View{$class=$this->model($resource);abort_unless($r->user()->can('list-'.$resource),403);$label=$resource==='gallery-albums'?'title':'name';$items=$class::with('translations')->when($r->filled('q'),fn($q)=>$q->whereTranslationLike($label,'%'.$r->q.'%'))->when($r->filled('status'),fn($q)=>$q->where('is_active',$r->boolean('status')))->orderBy('sort_order')->paginate(20)->withQueryString();return view('admin.domain_content.index',compact('items','resource','label'));}
 public function create(Request $r,string $resource):View{$class=$this->model($resource);abort_unless($r->user()->can('create-'.$resource),403);return $this->form($resource,new $class);}
 public function store(Request $r,string $resource):RedirectResponse{$class=$this->model($resource);abort_unless($r->user()->can('create-'.$resource),403);$class::create($this->data($r,$resource));return redirect()->route('domain-content.index',$resource)->with('message','Created.');}
 public function edit(Request $r,string $resource,int $item):View{$class=$this->model($resource);abort_unless($r->user()->can('edit-'.$resource),403);return $this->form($resource,$class::findOrFail($item));}
 public function update(Request $r,string $resource,int $item):RedirectResponse{$class=$this->model($resource);abort_unless($r->user()->can('edit-'.$resource),403);$model=$class::findOrFail($item);$model->update($this->data($r,$resource,$model));return back()->with('message','Updated.');}
 public function destroy(Request $r,string $resource,int $item):RedirectResponse{$class=$this->model($resource);abort_unless($r->user()->can('delete-'.$resource),403);$class::findOrFail($item)->delete();return back()->with('message','Deleted.');}
 public function storeMedia(Request $r,GalleryAlbum $album):RedirectResponse{abort_unless($r->user()->can('create-gallery-media'),403);$album->media()->create($this->mediaData($r));return back()->with('message','Media added.');}
 public function indexMedia(Request $r,GalleryAlbum $album):View{abort_unless($r->user()->can('list-gallery-media'),403);$media=$album->media()->with('translations')->when($r->filled('q'),fn($q)=>$q->where(fn($x)=>$x->where('path','like','%'.$r->q.'%')->orWhereTranslationLike('caption','%'.$r->q.'%')))->when($r->filled('type'),fn($q)=>$q->where('type',$r->type))->orderBy('sort_order')->paginate(20)->withQueryString();return view('admin.gallery_media.index',compact('album','media'));}
 public function editMedia(Request $r,GalleryAlbum $album,GalleryMedia $medium):View{abort_unless($r->user()->can('edit-gallery-media'),403);$this->mediaBelongsTo($album,$medium);return view('admin.gallery_media.form',compact('album','medium'));}
 public function updateMedia(Request $r,GalleryAlbum $album,GalleryMedia $medium):RedirectResponse{abort_unless($r->user()->can('edit-gallery-media'),403);$this->mediaBelongsTo($album,$medium);$medium->update($this->mediaData($r,$medium));return redirect()->route('gallery-media.index',$album)->with('message','Media updated.');}
 public function destroyMedia(Request $r,GalleryAlbum $album,GalleryMedia $medium):RedirectResponse{abort_unless($r->user()->can('delete-gallery-media'),403);abort_unless($medium->gallery_album_id===$album->id,404);$medium->delete();return back()->with('message','Media deleted.');}
 private function mediaBelongsTo(GalleryAlbum $album,GalleryMedia $medium):void{abort_unless($medium->gallery_album_id===$album->id,404);}
 private function mediaData(Request $r,?GalleryMedia $medium=null):array
 {
  $isVideo=$r->input('type')==='video';
  $d=$r->validate($this->mediaRules('thumbnail')+[
   'type'=>['required','in:image,video'],
   'file'=>['nullable','file','max:51200',$isVideo?'mimetypes:video/mp4,video/webm,video/quicktime':'image'],
   'path'=>[$medium||$r->hasFile('file')?'nullable':'required','string','max:500'],
   'sort_order'=>['nullable','integer','min:0'],
   'en.alt_text'=>['nullable','string','max:255'],
   'en.caption'=>['nullable','string','max:255'],
  ]);
  unset($d['file']);
  if($r->hasFile('file'))$d['path']=$isVideo?$this->media()->uploadFile($r->file('file')):$this->media()->upload($r->file('file'));
  elseif(!$r->filled('path'))$d['path']=$medium?->getRawOriginal('path');
  $this->resolveMedia($r,$d,'thumbnail',$medium?->getRawOriginal('thumbnail'));
  $d['sort_order']??=0;
  return $d;
 }
 private function model(string $r):string{abort_unless(isset(self::MAP[$r]),404);return self::MAP[$r];}
 private function form(string $resource,$item):View{return view('admin.domain_content.form',['resource'=>$resource,'item'=>$item,'destinations'=>Destination::withTranslation()->get(),'categories'=>GalleryCategory::withTranslation()->get(),'tours'=>Tour::withTranslation()->get()]);}
 private function data(Request $r,string $resource,$item=null):array{$common=['sort_order'=>['nullable','integer','min:0'],'is_active'=>['nullable','boolean']];if($resource==='visa-services')$rules=$common+['destination_id'=>['nullable','exists:destinations,id'],'price'=>['nullable','numeric','min:0'],'currency'=>['required','string','size:3'],'processing_time'=>['nullable','string','max:255'],'en.name'=>['required','string','max:255'],'en.description'=>['nullable','string'],'en.required_documents'=>['nullable','string'],'en.disclaimer'=>['nullable','string']];elseif($resource==='gallery-categories')$rules=$common+['en.name'=>['required','string','max:255'],'en.slug'=>['required','alpha_dash','max:255']];else $rules=$common+$this->mediaRules('cover_image')+['gallery_category_id'=>['nullable','exists:gallery_categories,id'],'tour_id'=>['nullable','exists:tours,id'],'destination_id'=>['nullable','exists:destinations,id'],'tour_date'=>['nullable','date'],'en.title'=>['required','string','max:255'],'en.description'=>['nullable','string']];$d=$r->validate($rules);if($resource==='gallery-albums')$this->resolveMedia($r,$d,'cover_image',$item?->getRawOriginal('cover_image'));$d['is_active']=$r->boolean('is_active');$d['sort_order']??=0;return $d;}
}
