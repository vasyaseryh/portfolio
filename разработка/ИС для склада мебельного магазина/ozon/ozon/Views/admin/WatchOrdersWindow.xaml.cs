using ozon.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.IO;
using System.Reflection;

namespace ozon.Views
{
    public partial class WatchOrdersWindow : Window
    {
        private List<dynamic> _ordersData;

        public WatchOrdersWindow()
        {
            InitializeComponent();
            LoadOrders();
        }

        private void LoadOrders()
        {
            using (var db = new OzonContext())
            {
                var ProductsOrders = db.Products.Join(db.Orders,
                    p => p.Id,
                    o => o.ProductId,
                    (p, o) => new
                    {
                        OrderId = o.Id,
                        ProductId = p.Id,
                        Name = p.Name,
                        Description = p.Description,
                        ImgUrl = p.ImgUrl,
                        Quantity = o.Quantity,
                        Address = o.Address,
                        Status = o.Status,
                        OPrice = o.Price,
                        PPrice = p.Price,
                        CreateTime = o.Create,
                        DeliveryTime = o.DeliveryTime,
                        ShipmentTime = o.ShipmentTime
                    });

                var result = ProductsOrders.AsEnumerable()
                                          .Select(o => new
                                          {
                                              o.OrderId,
                                              o.ProductId,
                                              o.Name,
                                              o.Description,
                                              ImgUrl = Path.GetFullPath(o.ImgUrl),
                                              o.Quantity,
                                              o.Address,
                                              o.Status,
                                              o.OPrice,
                                              o.PPrice,
                                              o.CreateTime,
                                              o.DeliveryTime,
                                              o.ShipmentTime,
                                              CreateTimeFormatted = o.CreateTime.ToString("dd.MM.yyyy HH:mm"),
                                              DeliveryTimeFormatted = FormatDateTime(o.DeliveryTime),
                                              ShipmentTimeFormatted = FormatDateTime(o.ShipmentTime),
                                              StatusColor = GetStatusColor(o.Status),
                                              TotalPrice = o.OPrice * o.Quantity
                                          })
                                          .ToList();

                _ordersData = result.Cast<dynamic>().ToList();
                productsList.ItemsSource = _ordersData;
            }
        }

        // Обработчик нажатия на карточку
        private void OnOrderSelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
        {
            var selectedItem = productsList.SelectedItem;
            if (selectedItem != null)
            {
                // Получаем данные через рефлексию
                var orderId = (int)selectedItem.GetType().GetProperty("OrderId").GetValue(selectedItem);
                var productId = (int)selectedItem.GetType().GetProperty("ProductId").GetValue(selectedItem);
                var name = (string)selectedItem.GetType().GetProperty("Name").GetValue(selectedItem);
                var quantity = (int)selectedItem.GetType().GetProperty("Quantity").GetValue(selectedItem);
                var address = (string)selectedItem.GetType().GetProperty("Address").GetValue(selectedItem);
                var status = (string)selectedItem.GetType().GetProperty("Status").GetValue(selectedItem);
                var totalPrice = (int)selectedItem.GetType().GetProperty("TotalPrice").GetValue(selectedItem);
                var createTime = (DateTime)selectedItem.GetType().GetProperty("CreateTime").GetValue(selectedItem);
                var deliveryTime = (DateTime?)selectedItem.GetType().GetProperty("DeliveryTime").GetValue(selectedItem);
                var shipmentTime = (DateTime?)selectedItem.GetType().GetProperty("ShipmentTime").GetValue(selectedItem);

                // Создаем объект с данными для редактирования
                var orderData = new OrderEditData
                {
                    OrderId = orderId,
                    ProductId = productId,
                    Name = name,
                    Quantity = quantity,
                    Address = address,
                    Status = status,
                    TotalPrice = totalPrice,
                    CreateTime = createTime,
                    DeliveryTime = deliveryTime,
                    ShipmentTime = shipmentTime
                };

                // Открываем окно редактирования
                var editWindow = new EditOrderWindow(orderData);
                editWindow.ShowDialog();

                // Обновляем список после закрытия окна редактирования
                if (editWindow.DialogResult == true)
                {
                    LoadOrders();
                }

                // Сбрасываем выделение
                productsList.SelectedItem = null;
            }
        }

        // Метод для форматирования DateTime?
        private string FormatDateTime(DateTime? dateTime)
        {
            if (dateTime.HasValue)
                return dateTime.Value.ToString("dd.MM.yyyy HH:mm");
            else
                return "Не указана";
        }

        // Метод для определения цвета статуса
        private string GetStatusColor(string status)
        {
            if (status == null) return "#6C757D";

            string statusLower = status.ToLower();

            switch (statusLower)
            {
                case "в пути на склад":
                case "processed":
                    return "#4CAF50";
                case "на складе":
                case "shipping":
                    return "#2196F3";
                case "ожидает отгрузки":
                case "completed":
                    return "#6C757D";
                case "отменен":
                case "cancelled":
                    return "#F44336";
                case "в пути к клиенту":
                case "pending":
                    return "#4CAF50";
                default:
                    return "#6C757D";
            }
        }
    }

    // Класс для передачи данных в окно редактирования
    public class OrderEditData
    {
        public int OrderId { get; set; }
        public int ProductId { get; set; }
        public string Name { get; set; }
        public int Quantity { get; set; }
        public string Address { get; set; }
        public string Status { get; set; }
        public decimal TotalPrice { get; set; }
        public DateTime CreateTime { get; set; }
        public DateTime? DeliveryTime { get; set; }
        public DateTime? ShipmentTime { get; set; }
    }
}