using ozon.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Windows;
using System.Windows.Controls;
using System.IO;

namespace ozon.Views
{
    public partial class WatchProductWindow : Window
    {
        private List<Product> products;

        public WatchProductWindow()
        {
            InitializeComponent();
            LoadProducts();
        }

        private void LoadProducts()
        {
            using (var context = new OzonContext())
            {
                products = context.Products
                    .ToList()
                    .Select(p => new Product
                    {
                        Id = p.Id,
                        Name = p.Name,
                        Description = p.Description,
                        ImgUrl = p.ImgUrl, 
                        Quantity = p.Quantity,
                        Price = p.Price,
                        lehgth = p.lehgth,
                        width = p.width,
                        height = p.height
                    })
                    .ToList();

                products.ForEach(p => p.Dimensions = $"{p.lehgth}×{p.width}×{p.height} см");

                var displayProducts = products.Select(p => new
                {
                    p.Id,
                    p.Name,
                    p.Description,
                    ImgUrl = GetAbsoluteImagePath(p.ImgUrl), 
                    p.Quantity,
                    p.Price,
                    p.Dimensions
                }).ToList();

                productsList.ItemsSource = displayProducts;
            }
        }

        private void OnProductSelectionChanged(object sender, SelectionChangedEventArgs e)
        {
            var selectedItem = productsList.SelectedItem;
            if (selectedItem != null)
            {
                // Получаем Id из анонимного типа
                var idProperty = selectedItem.GetType().GetProperty("Id");
                if (idProperty != null)
                {
                    int productId = (int)idProperty.GetValue(selectedItem);

                    // Находим оригинальный продукт с относительным путем
                    var selectedProduct = products.FirstOrDefault(p => p.Id == productId);
                    if (selectedProduct != null)
                    {
                        new ChangeWindow(selectedProduct).ShowDialog();
                        LoadProducts(); // Обновляем список после закрытия окна редактирования
                        productsList.SelectedItem = null; // Сбрасываем выделение
                    }
                }
            }
        }

        private void SearchTextBox_TextChanged(object sender, TextChangedEventArgs e)
        {
            using (var context = new OzonContext())
            {
                var searchTerm = SearchTextBox.Text.Trim().ToLower();

                products = context.Products
                    .Where(p => string.IsNullOrEmpty(searchTerm) || p.Name.ToLower().Contains(searchTerm))
                    .ToList()
                    .Select(p => new Product
                    {
                        Id = p.Id,
                        Name = p.Name,
                        Description = p.Description,
                        ImgUrl = p.ImgUrl, // Оставляем относительный путь
                        Quantity = p.Quantity,
                        Price = p.Price,
                        lehgth = p.lehgth,
                        width = p.width,
                        height = p.height
                    })
                    .ToList();

                // Добавляем вычисляемое свойство Dimensions
                products.ForEach(p => p.Dimensions = $"{p.lehgth}×{p.width}×{p.height} см");

                // Для отображения используем абсолютный путь
                var displayProducts = products.Select(p => new
                {
                    p.Id,
                    p.Name,
                    p.Description,
                    ImgUrl = GetAbsoluteImagePath(p.ImgUrl),
                    p.Quantity,
                    p.Price,
                    p.Dimensions
                }).ToList();

                productsList.ItemsSource = displayProducts;
            }
        }

        // Метод для получения абсолютного пути только для отображения
        private string GetAbsoluteImagePath(string relativePath)
        {
            if (string.IsNullOrEmpty(relativePath))
                return string.Empty;

            try
            {
                return Path.GetFullPath(relativePath);
            }
            catch
            {
                return relativePath;
            }
        }
    }
}