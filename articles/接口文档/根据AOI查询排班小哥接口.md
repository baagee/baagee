## 0 通用约定
### 签名
1. 双方约定一个对称密钥 secret_key 例如 "abcdefghijklmnop"
2. 按一下顺序构造被签名内容字符串（UTF-8编码）：

- secret_key （如abcdefghijklmnop）
- request_uri （如/consignor/cang/order/pushwithtask）
- reqeust_body_raw （如{"ts":153124523,"ver":1,"task_code":"ABCDEJKNPQ"}）
3. 将以上内容用 | 进行字符串拼接作为被签名内容，如abcdefghijklmnop|/consignor/cang/order/pushwithtask|{"ts":153124523,"ver":1,"task_code":"ABCDEJKNPQ"}
4. 对以上字符串进行sha1运算得到签名串40位字符串。如前面得到的字符串的签名结果是 843c25a6a73ca2685feb11d397cca34a7707c1ed
5. 由于POST的内容未来可能会有调整，所以请不要按照特定顺序拼接内容，必须要对完整的RequestBody进行签名和验签。
6. 验签注意，由于Java中很多框架支持自动读取JSON格式的数据，但是读取后并不能准确还原原始JSON字符串内容。为避免由于解析后重新生成JSON导致字符串不同，需要在框架解析之前，自行提取原始POST内容进行验签。如SpringMVC的一种实现方式：

```
@RequestMapping("/xxx/yyy/zzz")
public void xxxYyyZzz(@RequestBody String json,HttpServletRequest request) {
  String uri = "/xxx/yyy/zzz";
  String key = "abcdefghijklmnop";
  String toSign = key + "|" + uri + "|" + json;
  String signature = sha1(toSign);
}
```



## 1.根据AOI查询排班小哥接口

POST  /srm/api2/getcourierbyaoi?sign=XXXXXXXXXXXXXX&service_name=XXXX

Content-Type: application/json

#### 入参

元素名 | 类型 | 必填 | 描述
---|---|---|---
aoi_id | string | 是 | AOI编码
date | string | 是 | 日期（前后三天：3天前、今天、明天、后天）
type | string | 是 | 排班类型（收P，派D，收+派（P,D））
sys | string | 是 | 请求来源系统

#### 返回

元素名 | 类型 | 必填 | 描述
---|---|---|---
dept_code | string | 是 | AOI归属网点
aoi_name | string | 是 | AOI名称
aoi_area_list | list | 是 | 

##### aoi_area_list 结构

元素名 | 类型 | 必填 | 描述
---|---|---|---
aoi_area_code| string | 是 | AOI归属的AOI区域
schedule_list | list | 是 | 班次信息

##### schedule_list 结构

元素名 | 类型 | 必填 | 描述
---|---|---|---
batch_code | string | 是 | 班次code
batch_type | string | 是 | 收P、派D
batch_start_time | string | 是 | 班次开始时间：如 10:00
batch_end_time | string | 是 | 班次结束时间：如 12:00
courier_list | list | 是 | 收派员列表
weight_list | list | 是 | 重量段

##### courier_list 结构

元素名 | 类型 | 必填 | 描述
---|---|---|---
courier_code | string | 是 | 收派员工号
courier_name | string | 是 | 收派员姓名

#### weight_list 结构

元素名 | 类型 | 必填 | 描述
---|---|---|---
weight_lower | int | 是 | 最小重量(kg)
weight_upper | int | 是 | 最大重量(kg)

#### 入参示例
```
{
    "aoi_id":"AOI编码",
    "date":"日期 2020-01-01（前后三天：3天前、今天、明天、后天）",
    "type":"P|D|P,D"
}
```

#### 返回示例

```
{
    "errno":0,
    "errmsg":"错误信息...",
    "data":{
        "dept_code":"AOI归属网点代码",
        "aoi_name":"AOI名称",
        "aoi_area_list":[
            {
                "aoi_area_code":"AOI归属的AOI区域",
                "schedule_list":[
                    {
                        "batch_code":"班次code",
                        "batch_type":"收P or 派D",
                        "batch_start_time":"班次开始时间：如 10:00",
                        "batch_end_time":"班次结束时间：如 12:00",
                        "courier_list":[
                            {
                                "courier_code":"收派员工号",
                                "courier_name":"收派员姓名"
                            },
                            {
                                "courier_code":"收派员工号",
                                "courier_name":"收派员姓名"
                            }
                        ],
                        "weight":[
                            {
                                "weight_lower":0,
                                "weight_upper":10
                            }
                        ]
                    }
                ]
            }
        ]
    }
}
```
