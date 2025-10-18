<?php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use GatewayClient\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{

    public $avatar;

    public function __construct()
    {
        $this->middleware('auth');

        // 设置GatewayWorker服务的Register服务ip和端口
        Gateway::$registerAddress = '127.0.0.1:1238';

        // 设置默认头像
        $user         = Auth::user();
        $this->avatar = $user->avatar ?? 'https://images.dog.ceo//breeds//sheepdog-shetland//n02105855_4281.jpg';
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {

        $room_id = $request->room_id ? $request->room_id : '1';
        session()->put('room_id', $room_id);
        return view('home');
    }

    public function init(Request $request)
    {
        try {
            // 验证请求数据
            $request->validate([
                'client_id' => 'required|string',
            ]);

            Log::info('收到初始化请求', ['client_id' => $request->client_id, 'user_id' => Auth::id()]);

            // 绑定用户
            $this->bind($request);

            //在线用户
            $this->users();

            //历史记录
            $this->history();

            // 通知用户进入聊天室
            $this->login();

            return response()->json([
                'status'  => 'success',
                'message' => '初始化成功',
            ]);

        } catch (\Exception $e) {
            Log::error('Init error: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => '初始化失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function bind(Request $request)
    {
        $id        = Auth::id();
        $client_id = $request->client_id;

        Log::info("绑定用户: user_id={$id}, client_id={$client_id}");

        Gateway::bindUid($client_id, $id);

        Gateway::setSession($client_id, [
            'id'     => $id,
            'avatar' => Auth::user()->avatar,
            'name'   => Auth::user()->name,
        ]);

        Gateway::joinGroup($client_id, session('room_id'));
    }

    /**
     * 提示进入聊天室
     */
    private function login()
    {
        $user = Auth::user();

        // 确保 avatar 字段存在，如果不存在则使用默认值
        $avatar = $user->avatar ?? 'https://images.dog.ceo//breeds//sheepdog-shetland//n02105855_4281.jpg';
        $name   = $user->name ?? '匿名用户';

        $data = [
            'type' => 'say',
            'data' => [
                'avatar'  => $avatar,
                'name'    => $name,
                'content' => '进入了聊天室',
                'time'    => date("Y-m-d H:i:s", time()),
            ],
        ];

        try {
            Gateway::sendToGroup(session('room_id'), json_encode($data));
            Log::info('登录消息发送成功');
        } catch (\Exception $e) {
            Log::error('发送消息失败: ' . $e->getMessage());
        }

    }

    public function say(Request $request)
    {
        $avatar = $user->avatar ?? 'https://images.dog.ceo//breeds//sheepdog-shetland//n02105855_4281.jpg';
        $data   = [
            'type' => 'say',
            'data' => [
                'avatar'  => $this->avatar,
                'name'    => Auth::user()->name,
                'content' => $request->input('content'),
                'time'    => date("Y-m-d H:i:s", time()),
            ],
        ];

        //私聊
        if ($request->user_id) {
            $data['data']['name'] = Auth::user()->name . ' 对 ' . User::find($request->user_id)->name . ' 说：';
            Gateway::sendToUid($request->user_id, json_encode($data));
            Gateway::sendToUid(Auth::id(), json_encode($data));

            //私聊信息，只发给对应用户，不存数据库
            return;
        }

        Gateway::sendToGroup(session('room_id'), json_encode($data));

        //存入数据库，以后可以查询聊天记录
        Message::create([
            'user_id' => Auth::id(),
            'room_id' => session('room_id'),
            'content' => $request->input('content'),
        ]);

    }

    /**
     * 最新的5条聊天历史信息
     */
    private function history()
    {
        $data = ['type' => 'history'];

        $messages = Message::with('user')->where('room_id', session('room_id'))->orderBy('id', 'desc')->limit(5)->get();

        $data['data'] = $messages->map(function ($item, $key) {
            return [
                'avatar'  => $item->user->avatar,
                'name'    => $item->user->name,
                'content' => $item->content,
                'time'    => $item->created_at->format("Y-m-d H:i:s"),
            ];
        });

        Gateway::sendToUid(Auth::id(), json_encode($data));
    }

    /**
     * 当前在线用户
     */
    private function users()
    {
        $data = [
            'type' => 'users',
            'data' => Gateway::getClientSessionsByGroup(session('room_id')),
        ];

        Gateway::sendToGroup(session('room_id'), json_encode($data));
    }

}
